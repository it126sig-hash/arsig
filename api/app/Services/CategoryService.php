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
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->with('children');
            }])
            ->get();

        return $this->formatTree($categories);
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

    private function formatTree(Collection $categories): array
    {
        return $categories->map(function ($category) {
            return [
                'key' => (string) $category->id,
                'label' => $category->name,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'company_id' => $category->company_id,
                ],
                'children' => $this->formatTree($category->children),
                'icon' => $category->children->count() > 0 ? 'pi pi-fw pi-folder' : 'pi pi-fw pi-file',
            ];
        })->toArray();
    }
}
