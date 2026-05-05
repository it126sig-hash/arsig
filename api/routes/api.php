<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'version' => 'v1']);
    });

    // Auth routes
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
});
