<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

/**
 * Tells you what is actually deployed, for hosts with no shell access.
 *
 * Every check is read-only: it inspects classes, database schema and config,
 * and reports what is missing along with how to fix it.
 */
class DiagnosticsController extends Controller
{
    public function index(Request $request)
    {
        return view('diagnostics.index', [
            'groups' => [
                'Code files' => $this->codeChecks(),
                'Database' => $this->schemaChecks(),
                'Configuration' => $this->configChecks(),
                'Wallet activity' => $this->walletChecks(),
            ],
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'timezone' => config('app.timezone'),
            'serverTime' => now()->format('d M Y, H:i:s'),
        ]);
    }

    /** Does the deployed class have the methods later features depend on? */
    protected function codeChecks(): array
    {
        return [
            $this->check(
                'WhatsappAccount::blockedReason()',
                method_exists(\App\Models\WhatsappAccount::class, 'blockedReason'),
                'Guards sends and template submissions against a placeholder token.',
                'Add hasUsableToken() and blockedReason() to app/Models/WhatsappAccount.php.',
            ),
            $this->check(
                'MessageDispatcher takes WalletService',
                $this->constructorAccepts(\App\Services\MessageDispatcher::class, \App\Services\WalletService::class),
                'Without it, no message ever debits the wallet.',
                'Deploy the updated app/Services/MessageDispatcher.php.',
            ),
            $this->check(
                'WalletService exists',
                class_exists(\App\Services\WalletService::class),
                'Handles every credit and debit.',
                'Deploy app/Services/WalletService.php and app/Services/InsufficientBalance.php.',
            ),
            $this->check(
                'CampaignRunner exists',
                class_exists(\App\Services\CampaignRunner::class),
                'Sends without a queue worker.',
                'Deploy app/Services/CampaignRunner.php.',
            ),
            $this->check(
                'TransientSendFailure exists',
                class_exists(\App\Support\TransientSendFailure::class),
                'MessageDispatcher imports this; a fatal error occurs without it.',
                'Deploy app/Support/TransientSendFailure.php.',
            ),
            $this->check(
                'WhatsAppClient::describeError()',
                method_exists(\App\Services\WhatsAppClient::class, 'describeError'),
                'Turns Graph errors into readable messages.',
                'Deploy the updated app/Services/WhatsAppClient.php.',
            ),
            $this->check(
                'WhatsAppClient::subscribedApps()',
                method_exists(\App\Services\WhatsAppClient::class, 'subscribedApps'),
                'Checks whether your app receives webhook events.',
                'Deploy the updated app/Services/WhatsAppClient.php.',
            ),
            $this->check(
                'TemplateSyncService::sync() returns an array',
                $this->returnsArray(\App\Services\TemplateSyncService::class, 'sync'),
                'The older version returned an int and hid API failures.',
                'Deploy the updated app/Services/TemplateSyncService.php.',
            ),
            $this->check(
                'Settings page passes $subscribed',
                str_contains(
                    (string) @file_get_contents(app_path('Http/Controllers/WhatsappAccountController.php')),
                    "'subscribed' =>"
                ),
                'The settings view reads this; an older controller throws Undefined variable.',
                'Deploy the updated app/Http/Controllers/WhatsappAccountController.php.',
            ),
            $this->check(
                'Webhook timestamps are timezone-corrected',
                str_contains(
                    (string) @file_get_contents(app_path('Http/Controllers/WebhookController.php')),
                    "createFromTimestamp((int) \$status['timestamp'], config('app.timezone'))"
                ),
                'Carbon 3 returns UTC, putting receipts hours adrift of local events.',
                'Deploy the updated app/Http/Controllers/WebhookController.php.',
            ),
        ];
    }

    protected function schemaChecks(): array
    {
        return [
            $this->check('whatsapp_rates table', Schema::hasTable('whatsapp_rates'),
                'Holds Meta cost, markup and client price.', 'Run php artisan migrate.'),
            $this->check('wallet_transactions table', Schema::hasTable('wallet_transactions'),
                'The wallet ledger.', 'Run php artisan migrate.'),
            $this->check('wallet_topups table', Schema::hasTable('wallet_topups'),
                'Manual UPI top-up requests.', 'Run php artisan migrate.'),
            $this->check('messages.charged_at column', Schema::hasColumn('messages', 'charged_at'),
                'Records that a message was billed, so refunds do not double up.', 'Run php artisan migrate.'),
            $this->check('messages.meta_cost column', Schema::hasColumn('messages', 'meta_cost'),
                'Keeps margin reportable per message.', 'Run php artisan migrate.'),
            $this->check('users.is_platform_admin column', Schema::hasColumn('users', 'is_platform_admin'),
                'Gates the top-up approvals screen.', 'Run php artisan migrate.'),
            $this->check(
                'Rate card has rows',
                Schema::hasTable('whatsapp_rates') && DB::table('whatsapp_rates')->exists(),
                Schema::hasTable('whatsapp_rates')
                    ? DB::table('whatsapp_rates')->count().' rate rows found.'
                    : 'Table missing.',
                'Run php artisan db:seed --class=PricingSeeder, or insert rows with ISO country codes.',
            ),
        ];
    }

    protected function configChecks(): array
    {
        $sandbox = config('whatsapp.sandbox');

        return [
            $this->check('Live mode (sandbox off)', ! $sandbox,
                $sandbox ? 'Sandbox is ON — Graph calls are simulated.' : 'Graph calls go to Meta.',
                'Set WHATSAPP_SANDBOX=false.'),
            $this->check('Inline sending enabled', (bool) config('whatsapp.send_inline'),
                'Required when no queue worker can run.', 'Set WHATSAPP_SEND_INLINE=true.'),
            $this->check('Cron token set', filled(config('whatsapp.cron_token')),
                'Lets an external pinger drain pending messages.', 'Set CRON_TOKEN to a long random string.'),
            $this->check('UPI payee configured', config('wallet.upi.vpa') !== 'yourbusiness@upi',
                'Shown in the top-up QR code.', 'Set UPI_VPA and UPI_PAYEE_NAME.'),
            $this->check('Session driver is database', config('session.driver') === 'database',
                'File sessions are lost on ephemeral hosts.', 'Set SESSION_DRIVER=database.'),
        ];
    }

    protected function walletChecks(): array
    {
        if (! Schema::hasTable('wallet_transactions')) {
            return [$this->check('Ledger', false, 'Table missing.', 'Run php artisan migrate.')];
        }

        $debits = DB::table('wallet_transactions')->where('type', 'debit')->count();
        $charged = Schema::hasColumn('messages', 'charged_at')
            ? DB::table('messages')->whereNotNull('charged_at')->count()
            : 0;
        $sent = DB::table('messages')->where('direction', 'outbound')->whereNotNull('wamid')->count();

        return [
            $this->check(
                'Messages have been charged',
                $debits > 0 || $sent === 0,
                $sent.' sent, '.$charged.' marked charged, '.$debits.' debit rows in the ledger.',
                'If messages have gone out but there are no debit rows, MessageDispatcher is not the updated version.',
            ),
            $this->check(
                'Balance matches the ledger',
                $this->balanceMatchesLedger(),
                'The cached wallet_balance should equal the sum of the ledger.',
                'If these disagree, something wrote wallet_balance directly instead of using WalletService.',
            ),
        ];
    }

    protected function balanceMatchesLedger(): bool
    {
        foreach (DB::table('tenants')->select('id', 'wallet_balance')->get() as $tenant) {
            $ledger = (float) DB::table('wallet_transactions')
                ->where('tenant_id', $tenant->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) as total")
                ->value('total');

            if (abs($ledger - (float) $tenant->wallet_balance) > 0.0001) {
                return false;
            }
        }

        return true;
    }

    protected function check(string $label, bool $ok, string $detail, string $fix): array
    {
        return ['label' => $label, 'ok' => $ok, 'detail' => $detail, 'fix' => $fix];
    }

    /** Is $dependency among the constructor parameters of $class? */
    protected function constructorAccepts(string $class, string $dependency): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $constructor = (new ReflectionClass($class))->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            if ((string) $parameter->getType() === $dependency) {
                return true;
            }
        }

        return false;
    }

    protected function returnsArray(string $class, string $method): bool
    {
        if (! class_exists($class) || ! method_exists($class, $method)) {
            return false;
        }

        return (string) (new ReflectionClass($class))->getMethod($method)->getReturnType() === 'array';
    }
}
