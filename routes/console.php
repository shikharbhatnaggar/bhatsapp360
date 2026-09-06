<?php

use App\Models\WhatsappAccount;
use App\Services\TemplateSyncService;
use Illuminate\Support\Facades\Schedule;

// Pull the latest review status for every tenant's templates.
Schedule::call(function (TemplateSyncService $sync) {
    WhatsappAccount::query()->where('is_active', true)->each(fn ($account) => $sync->sync($account));
})->everyFifteenMinutes()->name('templates:sync')->withoutOverlapping();
