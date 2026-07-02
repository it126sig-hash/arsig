<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    public function getAll(): Collection
    {
        return Department::with('heads')->withCount('users')->orderBy('name')->get();
    }

    public function store(array $data): Department
    {
        $department = Department::create(['name' => $data['name']]);
        $department->heads()->sync($data['head_user_ids'] ?? []);

        return $department->load('heads');
    }

    public function update(Department $department, array $data): bool
    {
        $result = $department->update(['name' => $data['name']]);

        if (array_key_exists('head_user_ids', $data)) {
            $department->heads()->sync($data['head_user_ids']);
        }

        return $result;
    }

    public function destroy(Department $department): bool
    {
        if ($department->users()->exists()) {
            throw new \Exception('Departemen tidak dapat dihapus karena masih memiliki user.');
        }

        return $department->delete();
    }

    public function trashed(): Collection
    {
        return Department::onlyTrashed()->orderBy('name')->get();
    }

    public function restore(int $id): Department
    {
        $department = Department::onlyTrashed()->findOrFail($id);
        $department->restore();

        return $department->load('heads');
    }
}
