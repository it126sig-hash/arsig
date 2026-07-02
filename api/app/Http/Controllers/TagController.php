<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

class TagController extends BaseController
{
    public function __construct(
        private readonly TagService $service
    ) {}

    public function index(): JsonResponse
    {
        return $this->successResponse($this->service->getAll());
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->service->store($request->validated());
        return $this->successResponse($tag->load('creator:id,name'), 'Tag berhasil dibuat.', 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $this->service->update($tag, $request->validated());
        return $this->successResponse($tag->fresh()->load('creator:id,name'), 'Tag berhasil diperbarui.');
    }

    public function destroy(Tag $tag): JsonResponse
    {
        try {
            $this->service->destroy($tag);
            return $this->successResponse(null, 'Tag berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function trashed(): JsonResponse
    {
        return $this->successResponse($this->service->trashed());
    }

    public function restore(int $id): JsonResponse
    {
        $tag = $this->service->restore($id);
        return $this->successResponse($tag, 'Tag berhasil dipulihkan.');
    }
}
