<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminTopupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SandboxController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsappAccountController;
use Illuminate\Support\Facades\Route;

// Public and unauthenticated: Meta requires the privacy policy to be reachable
// without a login during app review.
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// ---------------------------------------------------------------- public
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Meta callbacks: no session, no CSRF, signature-verified inside the controller.
Route::get('/webhooks/whatsapp/{tenant}', [WebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp/{tenant}', [WebhookController::class, 'handle'])->name('webhooks.whatsapp');

// Stands in for queue:work and schedule:run where no shell is available.
Route::get('/cron/run/{token}', [CronController::class, 'run'])->name('cron.run');

// ---------------------------------------------------------------- console
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/settings/whatsapp', [WhatsappAccountController::class, 'edit'])->name('settings.whatsapp');
    Route::post('/settings/whatsapp', [WhatsappAccountController::class, 'save'])->name('settings.whatsapp.save');
    Route::post('/settings/whatsapp/verify', [WhatsappAccountController::class, 'verify'])->name('settings.whatsapp.verify');
    Route::post('/settings/whatsapp/subscribe', [WhatsappAccountController::class, 'subscribe'])->name('settings.whatsapp.subscribe');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/new', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/new', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::post('/templates/refresh', [TemplateController::class, 'refresh'])->name('templates.refresh');
    Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
    Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::post('/templates/{template}/submit', [TemplateController::class, 'submit'])->name('templates.submit');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/new', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns/preview', [CampaignController::class, 'preview'])->name('campaigns.preview');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campaigns/{campaign}/run', [CampaignController::class, 'run'])->name('campaigns.run');

    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/{customer}/reply', [InboxController::class, 'reply'])->name('inbox.reply');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topups', [WalletController::class, 'store'])->name('wallet.store');
    Route::get('/wallet/topups/{topup}', [WalletController::class, 'show'])->name('wallet.topup');
    Route::post('/wallet/topups/{topup}/submit', [WalletController::class, 'submit'])->name('wallet.submit');

    // Operator-only: credits a wallet, so it stays behind an admin check.
    Route::get('/admin/topups', [AdminTopupController::class, 'index'])->name('admin.topups');
    Route::post('/admin/topups/{topup}/approve', [AdminTopupController::class, 'approve'])->name('admin.topups.approve');
    Route::post('/admin/topups/{topup}/reject', [AdminTopupController::class, 'reject'])->name('admin.topups.reject');

    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('/diagnostics', [DiagnosticsController::class, 'index'])->name('diagnostics');

    // Sandbox-only demo triggers.
    Route::post('/sandbox/campaigns/{campaign}/advance', [SandboxController::class, 'advanceCampaign'])->name('sandbox.advance');
    Route::post('/sandbox/inbound', [SandboxController::class, 'inboundReply'])->name('sandbox.inbound');
});
