<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    public function getAll(): Collection
    {
        return Department::withCount('users')->orderBy('name')->get();
    }

    public function store(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): bool
    {
        return $department->update($data);
    }

    public function destroy(Department $department): bool
    {
        if ($department->users()->exists()) {
            throw new \Exception('Departemen tidak dapat dihapus karena masih memiliki user.');
        }

        return $department->delete();
    }
}
