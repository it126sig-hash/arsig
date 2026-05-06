<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class TagService
{
    public function getAll(): Collection
    {
        return Tag::with('creator:id,name')->orderBy('nama')->get();
    }

    public function store(array $data): Tag
    {
        $data['created_by'] = Auth::id() ?? 1;
        return Tag::create($data);
    }

    public function update(Tag $tag, array $data): bool
    {
        return $tag->update($data);
    }

    public function destroy(Tag $tag): bool
    {
        return $tag->delete();
    }
}
