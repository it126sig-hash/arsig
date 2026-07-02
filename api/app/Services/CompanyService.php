<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class CompanyService
{
    public function getAll(): Collection
    {
        return Company::orderBy('name')->get();
    }

    public function store(array $data): Company
    {
        return Company::create($data);
    }

    public function update(Company $company, array $data): bool
    {
        return $company->update($data);
    }

    public function destroy(Company $company): bool
    {
        if ($company->categories()->exists()) {
            throw new \Exception('Perusahaan tidak dapat dihapus karena masih memiliki kategori.');
        }

        return $company->delete();
    }

    public function trashed(): Collection
    {
        return Company::onlyTrashed()->orderBy('name')->get();
    }

    public function restore(int $id): Company
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->restore();

        return $company;
    }
}
