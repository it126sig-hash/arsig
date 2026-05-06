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
            ->with(['tags', 'accessDepartments', 'accessUsers'])
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
        $this->syncAccess($archive, $data);

        // Handle Tags
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $archive->tags()->sync($data['tag_ids']);
        }

        return $archive->load(['tags', 'accessDepartments', 'accessUsers', 'floor', 'room', 'cabinet', 'cabinetSlot']);
    }

    public function update(Archive $archive, array $data, ?UploadedFile $file): Archive
    {
        if ($file && in_array($data['archive_type'], ['full', 'digital_only'])) {
            // Delete old file if exists
            if ($archive->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($archive->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($archive->file_path);
            }

            $year = date('Y', strtotime($data['issue_date']));
            $path = "archives/{$data['company_id']}/{$year}";
            $data['file_path'] = $file->store($path, 'local');
        }

        $archive->update($data);

        // Sync Access
        $this->syncAccess($archive, $data);

        // Sync Tags
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $archive->tags()->sync($data['tag_ids']);
        }

        return $archive->load(['tags', 'accessDepartments', 'accessUsers', 'floor', 'room', 'cabinet', 'cabinetSlot']);
    }

    private function syncAccess(Archive $archive, array $data): void
    {
        $archive->privacyTargets()->delete();

        if ($data['privacy_type'] === 'department' && isset($data['department_ids'])) {
            foreach ($data['department_ids'] as $deptId) {
                $archive->privacyTargets()->create(['department_id' => (int) $deptId]);
            }
        } elseif ($data['privacy_type'] === 'user' && isset($data['user_ids'])) {
            foreach ($data['user_ids'] as $userId) {
                $archive->privacyTargets()->create(['user_id' => (int) $userId]);
            }
        }
    }
}
