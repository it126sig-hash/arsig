<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Archive;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class ArchiveService
{
    public function list(?int $companyId = null, ?int $categoryId = null)
    {
        return Archive::query()
            ->with(['tags'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();
    }

    public function store(array $data, ?UploadedFile $file): Archive
    {
        if ($file && in_array($data['archive_type'], ['full', 'digital_only'])) {
            $year = date('Y', strtotime($data['issue_date']));
            $path = "archives/{$data['company_id']}/{$year}";
            $data['file_path'] = $file->store($path, 'local');
        }

        $data['created_by'] = Auth::id() ?? 1; // Fallback for testing
        $data['status'] = 'active';

        $archive = Archive::create($data);

        // Handle Privacy Access
        if ($data['privacy_type'] === 'department' && isset($data['department_ids'])) {
            $archive->accessDepartments()->sync($data['department_ids']);
        } elseif ($data['privacy_type'] === 'user' && isset($data['user_ids'])) {
            $archive->accessUsers()->sync($data['user_ids']);
        }

        // Handle Tags
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $archive->tags()->sync($data['tag_ids']);
        }

        return $archive;
    }
}
