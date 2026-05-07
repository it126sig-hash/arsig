<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ArchiveDownloadRequest;
use App\Services\ArchiveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArchiveRequestController extends BaseController
{
    public function __construct(
        private readonly ArchiveRequestService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $requests = $this->service->listForPic(Auth::user());
        return $this->successResponse($requests, 'Daftar permintaan akses berhasil diambil.');
    }

    public function approve(ArchiveDownloadRequest $archiveRequest): JsonResponse
    {
        // Simple authorization check: only PIC or Admin
        $user = Auth::user();
        if ($user->role !== 'admin' && $archiveRequest->archive->pic_user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk menyetujui permintaan ini.');
        }

        if ($archiveRequest->status !== 'pending') {
            abort(400, 'Permintaan ini sudah diproses.');
        }

        $result = $this->service->approve($archiveRequest, $user);
        return $this->successResponse($result, 'Permintaan akses disetujui.');
    }

    public function reject(ArchiveDownloadRequest $archiveRequest): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $archiveRequest->archive->pic_user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk menolak permintaan ini.');
        }

        if ($archiveRequest->status !== 'pending') {
            abort(400, 'Permintaan ini sudah diproses.');
        }

        $result = $this->service->reject($archiveRequest, $user);
        return $this->successResponse($result, 'Permintaan akses ditolak.');
    }
}
