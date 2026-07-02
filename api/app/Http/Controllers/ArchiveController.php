<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreArchiveRequest;
use App\Http\Requests\UpdateArchiveRequest;
use App\Models\Archive;
use App\Models\ArchiveDownloadRequest;
use App\Services\ArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Requests\MoveArchiveLocationRequest;
use App\Models\ArchiveLocationLog;

class ArchiveController extends BaseController
{
    public function __construct(
        private readonly ArchiveService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $archives = $this->service->list(
            companyId: $request->integer('company_id') ?: null,
            categoryId: $request->integer('category_id') ?: null,
            q: $request->string('q')->trim()->toString() ?: null,
            archiveType: $request->string('archive_type')->toString() ?: null,
            dateFrom: $request->string('date_from')->toString() ?: null,
            dateTo: $request->string('date_to')->toString() ?: null,
            tagIds: $request->input('tag_ids', []),
            filterExpiring: $request->boolean('filter_expiring'),
            filterBorrowed: $request->boolean('filter_borrowed'),
            filterInCabinet: $request->boolean('filter_in_cabinet')
        );

        return $this->successResponse($archives, 'Daftar arsip berhasil diambil.');
    }

    public function store(StoreArchiveRequest $request): JsonResponse
    {
        $archive = $this->service->store(
            $request->validated(),
            $request->file('file')
        );

        return $this->successResponse($archive, 'Arsip berhasil diupload.', 201);
    }

    public function update(UpdateArchiveRequest $request, Archive $archive): JsonResponse
    {
        $this->authorize('update', $archive);

        $updated = $this->service->update(
            $archive,
            $request->validated(),
            $request->file('file')
        );

        return $this->successResponse($updated, 'Arsip berhasil diperbarui.');
    }

    public function download(Archive $archive)
    {
        $this->authorize('view', $archive);

        if ($archive->archive_type === 'placeholder') {
            abort(400, 'Berkas fisik tidak tersedia untuk di-download secara digital.');
        }

        if (!$archive->file_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($archive->file_path)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        $downloader = \Illuminate\Support\Facades\Auth::user();

        \App\Models\ArchiveDownloadLog::create([
            'archive_id' => $archive->id,
            'user_id' => $downloader->id,
            'action' => 'download',
            'created_at' => now(),
        ]);

        if ($archive->pic && (int) $archive->pic_user_id !== (int) $downloader->id) {
            $archive->pic->notify(new \App\Notifications\ArchiveDownloadedNotification($archive, $downloader));
        }

        $extension = pathinfo($archive->file_path, PATHINFO_EXTENSION);
        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $archive->file_path,
            $archive->name . ($extension ? '.' . $extension : '')
        );
    }

    public function logView(Archive $archive): JsonResponse
    {
        $this->authorize('view', $archive);

        \App\Models\ArchiveDownloadLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'action' => 'view',
            'created_at' => now(),
        ]);

        return $this->successResponse(null, 'Riwayat lihat detail tercatat.');
    }

    public function preview(Archive $archive)
    {
        $this->authorize('view', $archive);

        if ($archive->archive_type === 'placeholder') {
            abort(400, 'Berkas fisik tidak memiliki tampilan digital.');
        }

        if (!$archive->file_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($archive->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $contentType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($archive->file_path);
        
        return \Illuminate\Support\Facades\Storage::disk('local')->response($archive->file_path, null, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline'
        ]);
    }

    public function requestOtp(Archive $archive): JsonResponse
    {
        $userId = Auth::id();

        // Cek apakah ada request aktif: status pending, atau approved & OTP belum expire
        $existingRequest = ArchiveDownloadRequest::where('archive_id', $archive->id)
            ->where('requester_user_id', $userId)
            ->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'approved')
                         ->where('otp_expires_at', '>', now());
                  });
            })
            ->first();

        if ($existingRequest) {
            $message = $existingRequest->status === 'pending'
                ? ($existingRequest->approval_stage === 'department'
                    ? 'Permintaan OTP Anda sedang menunggu persetujuan kepala departemen.'
                    : 'Permintaan OTP Anda sedang diproses oleh PIC. Harap tunggu persetujuan.')
                : 'Kode OTP sebelumnya masih aktif. Silakan gunakan OTP yang telah dikirimkan.';

            return $this->errorResponse($message, null, 400);
        }

        // Buat request baru ke PIC
        $downloadRequest = ArchiveDownloadRequest::create([
            'archive_id'          => $archive->id,
            'requester_user_id'   => $userId,
            'status'              => 'pending',
            'requires_department_approval' => (bool) $archive->is_confidential,
            'approval_stage'      => 'pic',
        ]);

        // Kirim notifikasi ke PIC
        if ($archive->pic) {
            $archive->pic->notify(new \App\Notifications\OtpRequestedNotification($archive, Auth::user()));
        }

        return $this->successResponse(null, 'Permintaan OTP telah dikirim ke PIC. Harap tunggu persetujuan.');
    }

    public function verifyOtp(Request $request, Archive $archive): JsonResponse
    {
        $otp    = $request->string('otp')->toString();
        $userId = Auth::id();

        $downloadRequest = ArchiveDownloadRequest::where('archive_id', $archive->id)
            ->where('requester_user_id', $userId)
            ->where('status', 'approved')
            ->where('otp_code', $otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (! $downloadRequest) {
            return $this->errorResponse('Kode OTP tidak valid atau sudah kadaluarsa.', 422);
        }

        $downloadRequest->update(['is_verified' => true]);

        return $this->successResponse(null, 'OTP berhasil diverifikasi. Akses diberikan.');
    }

    public function moveLocation(MoveArchiveLocationRequest $request, Archive $archive): JsonResponse
    {
        $this->authorize('moveLocation', $archive);

        $updated = $this->service->moveLocation($archive, $request->validated());

        return $this->successResponse($updated, 'Lokasi fisik arsip berhasil dipindahkan.');
    }

    public function locationHistories(Request $request, Archive $archive): JsonResponse
    {
        $this->authorize('viewLocation', $archive);

        $query = ArchiveLocationLog::where('archive_id', $archive->id)
            ->with([
                'movedBy', 
                'oldFloor', 'oldRoom', 'oldCabinet', 'oldCabinetSlot',
                'newFloor', 'newRoom', 'newCabinet', 'newCabinetSlot'
            ])
            ->orderByDesc('created_at');

        if ($request->hasAny(['page', 'per_page'])) {
            $perPage = min(max((int) $request->integer('per_page', 10), 1), 25);

            return $this->successResponse(
                $query->paginate($perPage),
                'Riwayat lokasi arsip berhasil diambil.'
            );
        }

        $histories = $query->limit(10)->get();

        return $this->successResponse($histories, 'Riwayat lokasi arsip berhasil diambil.');
    }
}
