<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MetaWebhookController;

Route::match(['get', 'post'], '/webhook', [MetaWebhookController::class, 'handle']);