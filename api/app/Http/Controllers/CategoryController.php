<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function __construct(
        private readonly CategoryService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        // For simplicity, using company_id from request or default
        $companyId = (int) ($request->company_id ?? 1); 
        $tree = $this->service->getTree($companyId);
        return $this->successResponse($tree);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->store($request->validated());
        return $this->successResponse($category, 'Kategori berhasil dibuat.', 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->service->update($category, $request->validated());
        return $this->successResponse($category, 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->service->destroy($category);
            return $this->successResponse(null, 'Kategori berhasil dihapus.');
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
        $category = $this->service->restore($id);
        return $this->successResponse($category, 'Kategori berhasil dipulihkan.');
    }
}
