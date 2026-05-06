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
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();
    }

    public function store(array $data, ?UploadedFile $file): Archive
    {
        if ($file && $data['archive_type'] === 'full') {
            $year = date('Y', strtotime($data['issue_date']));
            $path = "archives/{$data['company_id']}/{$year}";
            $data['file_path'] = $file->store($path, 'private');
        }

        $data['created_by'] = Auth::id() ?? 1; // Fallback for testing
        $data['status'] = 'active';

        return Archive::create($data);
    }
}
