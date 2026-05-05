<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'version' => 'v1']);
    });

    // Auth routes
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        
        Route::apiResource('floors', \App\Http\Controllers\Api\FloorController::class);
        Route::apiResource('rooms', \App\Http\Controllers\Api\RoomController::class);
        Route::apiResource('cabinets', \App\Http\Controllers\Api\CabinetController::class);
        Route::apiResource('cabinet-slots', \App\Http\Controllers\Api\CabinetSlotController::class);
    });
});
