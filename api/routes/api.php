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

        // Companies
        Route::apiResource('companies', \App\Http\Controllers\CompanyController::class);

        // Departments
        Route::apiResource('departments', \App\Http\Controllers\DepartmentController::class);

        // Users
        Route::apiResource('users', \App\Http\Controllers\UserController::class);

        // Tags
        Route::apiResource('tags', \App\Http\Controllers\TagController::class);

        // Categories & Archives
        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
        Route::get('archives', [\App\Http\Controllers\ArchiveController::class, 'index']);
        Route::post('archives', [\App\Http\Controllers\ArchiveController::class, 'store']);
    });
});
