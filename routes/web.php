<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\SandboxController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsappAccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CronController;

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

// ---------------------------------------------------------------- console
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/settings/whatsapp', [WhatsappAccountController::class, 'edit'])->name('settings.whatsapp');
    Route::post('/settings/whatsapp', [WhatsappAccountController::class, 'save'])->name('settings.whatsapp.save');
    Route::post('/settings/whatsapp/verify', [WhatsappAccountController::class, 'verify'])->name('settings.whatsapp.verify');

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

    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/{customer}/reply', [InboxController::class, 'reply'])->name('inbox.reply');

    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    // Sandbox-only demo triggers.
    Route::post('/sandbox/campaigns/{campaign}/advance', [SandboxController::class, 'advanceCampaign'])->name('sandbox.advance');
    Route::post('/sandbox/inbound', [SandboxController::class, 'inboundReply'])->name('sandbox.inbound');

    Route::get('/cron/run/{token}', [CronController::class, 'run'])->name('cron.run');
    // and inside the auth group:
    Route::post('/campaigns/{campaign}/run', [CampaignController::class, 'run'])->name('campaigns.run');
});



Route::get('/view-logs-securely', function () {
    $logPath = storage_path('logs/laravel.log');

    if (!file_exists($logPath)) {
        return response()->json(['message' => 'Log file does not exist yet. No errors recorded.']);
    }

    // Read the last 50 lines of the log file to avoid overloading the browser
    $file = file($logPath);
    $lastLines = array_slice($file, -50); 
    
    return response(implode("", $lastLines), 200)
        ->header('Content-Type', 'text/plain');
});

