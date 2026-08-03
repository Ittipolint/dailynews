<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are used by n8n workflows and external systems.
| All endpoints are protected by API token / signature verification.
*/

Route::prefix('v1')->middleware('api.token')->group(function (): void {
    Route::get('news', [\App\Http\Controllers\Api\NewsApiController::class, 'index']);
    Route::get('news/{news}', [\App\Http\Controllers\Api\NewsApiController::class, 'show']);

    Route::get('sources', [\App\Http\Controllers\Api\SourceApiController::class, 'index']);

    Route::get('schedules/due', [\App\Http\Controllers\Api\DeliveryApiController::class, 'dueSchedules']);
    Route::post('deliver', [\App\Http\Controllers\Api\DeliveryApiController::class, 'run']);
    Route::get('deliveries', [\App\Http\Controllers\Api\DeliveryApiController::class, 'index']);
    Route::post('deliveries', [\App\Http\Controllers\Api\DeliveryApiController::class, 'record']);
});

Route::prefix('ingest')->group(function (): void {
    Route::post('push', [\App\Http\Controllers\Api\IngestApiController::class, 'push']);
});

Route::prefix('line')->group(function (): void {
    Route::post('webhook', [\App\Http\Controllers\Api\LineWebhookController::class, 'handle']);
});
