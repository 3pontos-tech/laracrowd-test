<?php

use Illuminate\Support\Facades\Route;
use Platform\Placements\Http\Controllers\SignatureWebhookController;

Route::post('webhooks/signature', SignatureWebhookController::class)
    ->name('webhooks.signature');
