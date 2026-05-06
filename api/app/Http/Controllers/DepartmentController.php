<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;

class DepartmentController extends BaseController
{
    public function __construct(
        private readonly DepartmentService $service
    ) {}

    public function index(): JsonResponse
    {
        return $this->successResponse($this->service->getAll());
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->service->store($request->validated());
        return $this->successResponse($department, 'Departemen berhasil dibuat.', 201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->service->update($department, $request->validated());
        return $this->successResponse($department->fresh(), 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department): JsonResponse
    {
        try {
            $this->service->destroy($department);
            return $this->successResponse(null, 'Departemen berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
