<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Archive;
use App\Services\ArchiveCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ArchiveCheckoutController extends BaseController
{
    public function __construct(
        private readonly ArchiveCheckoutService $service
    ) {}

    public function checkout(CheckoutRequest $request, Archive $archive): JsonResponse
    {
        $this->authorizeCheckout($archive);

        $log = $this->service->checkout(
            $archive,
            $request->validated(),
            Auth::user()
        );

        return $this->successResponse($log, 'Arsip berhasil dikeluarkan.');
    }

    public function checkin(Archive $archive): JsonResponse
    {
        $this->authorizeCheckout($archive);

        $log = $this->service->checkin($archive, Auth::user());

        return $this->successResponse($log, 'Arsip berhasil ditandai kembali.');
    }

    public function history(Archive $archive): JsonResponse
    {
        $this->authorize('view', $archive);

        $history = $this->service->getHistory($archive);

        return $this->successResponse($history, 'Riwayat checkout arsip berhasil diambil.');
    }

    private function authorizeCheckout(Archive $archive): void
    {
        $user = Auth::user();

        $isPic = $archive->pic_user_id === $user->id;
        $isAdminOrRoot = in_array($user->role, ['admin', 'root']);

        if (!$isPic && !$isAdminOrRoot) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah status checkout arsip ini.');
        }
    }
}
