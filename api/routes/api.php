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
        Route::put('archives/{archive}', [\App\Http\Controllers\ArchiveController::class, 'update']);
        Route::get('archives/{archive}/download', [\App\Http\Controllers\ArchiveController::class, 'download']);
        Route::get('archives/{archive}/preview', [\App\Http\Controllers\ArchiveController::class, 'preview']);
        Route::post('archives/{archive}/request-otp', [\App\Http\Controllers\ArchiveController::class, 'requestOtp']);
        Route::post('archives/{archive}/verify-otp', [\App\Http\Controllers\ArchiveController::class, 'verifyOtp']);

        // Archive Access Requests (for PIC/Admin)
        Route::get('archive-requests', [\App\Http\Controllers\ArchiveRequestController::class, 'index']);
        Route::post('archive-requests/{archiveRequest}/approve', [\App\Http\Controllers\ArchiveRequestController::class, 'approve']);
        Route::post('archive-requests/{archiveRequest}/reject', [\App\Http\Controllers\ArchiveRequestController::class, 'reject']);
        
        // Location History
        Route::get('archives/{archive}/location-histories', [\App\Http\Controllers\ArchiveController::class, 'locationHistories']);
        Route::post('archives/{archive}/move-location', [\App\Http\Controllers\ArchiveController::class, 'moveLocation']);
        Route::get('location-histories', [\App\Http\Controllers\LocationHistoryController::class, 'index']);

        // Archive Checkout
        Route::post('archives/{archive}/checkout', [\App\Http\Controllers\ArchiveCheckoutController::class, 'checkout']);
        Route::post('archives/{archive}/checkin', [\App\Http\Controllers\ArchiveCheckoutController::class, 'checkin']);
        Route::get('archives/{archive}/checkout-history', [\App\Http\Controllers\ArchiveCheckoutController::class, 'history']);
        // Dashboard Stats
        Route::get('dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
    });
});
