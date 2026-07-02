<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryService
{
    public function getTree(int $companyId): array
    {
        $categories = Category::where('company_id', $companyId)
            ->select(['id', 'company_id', 'name', 'parent_id'])
            ->orderBy('parent_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
        $grouped = $categories->groupBy(fn (Category $category): string => (string) ($category->parent_id ?? 'root'));

        return $this->formatTree($grouped);
    }

    public function store(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function destroy(Category $category): bool
    {
        // Check if category has archives
        if ($category->archives()->exists()) {
            throw new \Exception('Kategori tidak dapat dihapus karena masih memiliki arsip.');
        }

        // Check if category has children
        if ($category->children()->exists()) {
            throw new \Exception('Kategori tidak dapat dihapus karena masih memiliki sub-kategori.');
        }

        return $category->delete();
    }

    public function trashed(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::onlyTrashed()->orderBy('name')->get();
    }

    public function restore(int $id): Category
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return $category;
    }

    private function formatTree(Collection $grouped, ?int $parentId = null): array
    {
        $key = $parentId === null ? 'root' : (string) $parentId;

        return $grouped->get($key, collect())->map(function (Category $category) use ($grouped) {
            $children = $this->formatTree($grouped, $category->id);

            return [
                'key' => (string) $category->id,
                'label' => $category->name,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'company_id' => $category->company_id,
                ],
                'children' => $children,
                'icon' => count($children) > 0 ? 'pi pi-fw pi-folder' : 'pi pi-fw pi-file',
            ];
        })->values()->toArray();
    }
}
