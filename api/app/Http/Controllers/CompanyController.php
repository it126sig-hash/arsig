<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;

class CompanyController extends BaseController
{
    public function __construct(
        private readonly CompanyService $service
    ) {}

    public function index(): JsonResponse
    {
        return $this->successResponse($this->service->getAll());
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->service->store($request->validated());
        return $this->successResponse($company, 'Perusahaan berhasil dibuat.', 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $this->service->update($company, $request->validated());
        return $this->successResponse($company->fresh(), 'Perusahaan berhasil diperbarui.');
    }

    public function destroy(Company $company): JsonResponse
    {
        try {
            $this->service->destroy($company);
            return $this->successResponse(null, 'Perusahaan berhasil dihapus.');
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
        $company = $this->service->restore($id);
        return $this->successResponse($company, 'Perusahaan berhasil dipulihkan.');
    }
}
