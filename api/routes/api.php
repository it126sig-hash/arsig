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
        
        Route::middleware('module.permission:floors')->group(function () {
            Route::get('floors/trashed', [\App\Http\Controllers\Api\FloorController::class, 'trashed']);
            Route::apiResource('floors', \App\Http\Controllers\Api\FloorController::class);
        });
        Route::post('floors/{id}/restore', [\App\Http\Controllers\Api\FloorController::class, 'restore'])
            ->middleware('module.permission:floors,delete');

        Route::middleware('module.permission:rooms')->group(function () {
            Route::get('rooms/trashed', [\App\Http\Controllers\Api\RoomController::class, 'trashed']);
            Route::apiResource('rooms', \App\Http\Controllers\Api\RoomController::class);
        });
        Route::post('rooms/{id}/restore', [\App\Http\Controllers\Api\RoomController::class, 'restore'])
            ->middleware('module.permission:rooms,delete');

        Route::middleware('module.permission:cabinets')->group(function () {
            Route::get('cabinets/trashed', [\App\Http\Controllers\Api\CabinetController::class, 'trashed']);
            Route::apiResource('cabinets', \App\Http\Controllers\Api\CabinetController::class);
        });
        Route::post('cabinets/{id}/restore', [\App\Http\Controllers\Api\CabinetController::class, 'restore'])
            ->middleware('module.permission:cabinets,delete');

        Route::middleware('module.permission:cabinet_slots')->group(function () {
            Route::get('cabinet-slots/trashed', [\App\Http\Controllers\Api\CabinetSlotController::class, 'trashed']);
            Route::apiResource('cabinet-slots', \App\Http\Controllers\Api\CabinetSlotController::class);
        });
        Route::post('cabinet-slots/{id}/restore', [\App\Http\Controllers\Api\CabinetSlotController::class, 'restore'])
            ->middleware('module.permission:cabinet_slots,delete');

        // Companies
        Route::middleware('module.permission:companies')->group(function () {
            Route::get('companies/trashed', [\App\Http\Controllers\CompanyController::class, 'trashed']);
            Route::apiResource('companies', \App\Http\Controllers\CompanyController::class);
        });
        Route::post('companies/{id}/restore', [\App\Http\Controllers\CompanyController::class, 'restore'])
            ->middleware('module.permission:companies,delete');

        // Departments
        Route::middleware('module.permission:departments')->group(function () {
            Route::get('departments/trashed', [\App\Http\Controllers\DepartmentController::class, 'trashed']);
            Route::apiResource('departments', \App\Http\Controllers\DepartmentController::class);
        });
        Route::post('departments/{id}/restore', [\App\Http\Controllers\DepartmentController::class, 'restore'])
            ->middleware('module.permission:departments,delete');

        // Users (no soft delete)
        Route::middleware('module.permission:users')->group(function () {
            Route::apiResource('users', \App\Http\Controllers\UserController::class);
            Route::get('users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'show']);
            Route::put('users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'update']);
        });
        Route::delete('users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'reset'])
            ->middleware('module.permission:users,delete');

        // Tags (already soft-deletable)
        Route::middleware('module.permission:tags')->group(function () {
            Route::get('tags/trashed', [\App\Http\Controllers\TagController::class, 'trashed']);
            Route::apiResource('tags', \App\Http\Controllers\TagController::class);
        });
        Route::post('tags/{id}/restore', [\App\Http\Controllers\TagController::class, 'restore'])
            ->middleware('module.permission:tags,delete');

        // Role permission matrix (admin/root only — checked in controller, not module-gated)
        Route::get('role-permissions', [\App\Http\Controllers\RolePermissionController::class, 'index']);
        Route::put('role-permissions', [\App\Http\Controllers\RolePermissionController::class, 'update']);

        // Per-user permission overrides
        Route::get('my-permissions', [\App\Http\Controllers\UserPermissionController::class, 'mine']);

        // Categories & Archives
        Route::middleware('module.permission:categories')->group(function () {
            Route::get('categories/trashed', [\App\Http\Controllers\CategoryController::class, 'trashed']);
            Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
        });
        Route::post('categories/{id}/restore', [\App\Http\Controllers\CategoryController::class, 'restore'])
            ->middleware('module.permission:categories,delete');
        Route::get('archives', [\App\Http\Controllers\ArchiveController::class, 'index']);
        Route::post('archives', [\App\Http\Controllers\ArchiveController::class, 'store']);
        Route::put('archives/{archive}', [\App\Http\Controllers\ArchiveController::class, 'update']);
        Route::get('archives/{archive}/download', [\App\Http\Controllers\ArchiveController::class, 'download']);
        Route::get('archives/{archive}/preview', [\App\Http\Controllers\ArchiveController::class, 'preview']);
        Route::post('archives/{archive}/log-view', [\App\Http\Controllers\ArchiveController::class, 'logView']);
        Route::post('archives/{archive}/request-otp', [\App\Http\Controllers\ArchiveController::class, 'requestOtp']);
        Route::post('archives/{archive}/verify-otp', [\App\Http\Controllers\ArchiveController::class, 'verifyOtp']);

        // Archive Access Requests (for PIC/Admin)
        Route::get('archive-requests', [\App\Http\Controllers\ArchiveRequestController::class, 'index']);
        Route::get('archive-requests/my-requests', [\App\Http\Controllers\ArchiveRequestController::class, 'myRequests']);
        Route::post('archive-requests/{archiveRequest}/approve', [\App\Http\Controllers\ArchiveRequestController::class, 'approve']);
        Route::post('archive-requests/{archiveRequest}/reject', [\App\Http\Controllers\ArchiveRequestController::class, 'reject']);
        Route::get('archive-downloads/history', [\App\Http\Controllers\ArchiveRequestController::class, 'downloadHistory']);

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

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::post('notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    });
});
