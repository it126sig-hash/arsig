<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends BaseController
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        return $this->successResponse(
            $user->notifications()->paginate(20),
            'Daftar notifikasi berhasil diambil.'
        );
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->successResponse(null, 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->successResponse(null, 'Semua notifikasi ditandai sudah dibaca.');
    }
}
