<?php

// routes/webhooks.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\WebhookIngestController;

// no /api prefix; adjust if you want it under /api
Route::post('/ingest', WebhookIngestController::class)->name('webhooks.ingest');

// optional provider-aware endpoint, e.g. /webhooks/stripe
Route::post('/webhooks/{provider}', WebhookIngestController::class)->name('webhooks.provider');
