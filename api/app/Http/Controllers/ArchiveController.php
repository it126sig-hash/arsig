<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreArchiveRequest;
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
            $request->integer('company_id') ?: null,
            $request->integer('category_id') ?: null
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
}
