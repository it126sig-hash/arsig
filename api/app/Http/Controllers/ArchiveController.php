<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreArchiveRequest;
use App\Http\Requests\UpdateArchiveRequest;
use App\Models\Archive;
use App\Services\ArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            tagIds: $request->input('tag_ids', [])
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

        $extension = pathinfo($archive->file_path, PATHINFO_EXTENSION);
        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $archive->file_path, 
            $archive->name . ($extension ? '.' . $extension : '')
        );
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
        // Mock sending OTP
        return $this->successResponse(null, 'OTP has been sent to your registered device/email.');
    }

    public function verifyOtp(Request $request, Archive $archive): JsonResponse
    {
        $otp = $request->string('otp')->toString();

        if ($otp === '123456') { // Mock OTP validation
            return $this->successResponse(['token' => 'mock-access-token'], 'OTP verified successfully.');
        }

        return $this->errorResponse('Invalid OTP code.', 422);
    }
}
