<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Archive;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class ArchiveService
{
    public function list(
        ?int $companyId = null,
        ?int $categoryId = null,
        ?string $q = null,
        ?string $archiveType = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        array $tagIds = []
    ) {
        return Archive::query()
            ->with(['tags', 'accessDepartments', 'accessUsers', 'category', 'company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($archiveType, fn ($q) => $q->where('archive_type', $archiveType))
            ->when($dateFrom, fn ($q) => $q->whereDate('issue_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('issue_date', '<=', $dateTo))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('file_number', 'like', "%{$q}%")
                        ->orWhere('keterangan', 'like', "%{$q}%");
                });
            })
            ->when(!empty($tagIds), function ($query) use ($tagIds) {
                $query->whereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds));
            })
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
            $data['file_type'] = $file->getClientOriginalExtension();
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
        // Lokasi fisik TIDAK boleh diubah lewat endpoint edit archive.
        // Gunakan endpoint "Pindah Lokasi" yang terpisah.
        unset($data['floor_id'], $data['room_id'], $data['cabinet_id'], $data['cabinet_slot_id']);

        if ($file && in_array($data['archive_type'], ['full', 'digital_only'])) {
            // Delete old file if exists
            if ($archive->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($archive->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($archive->file_path);
            }

            $year = date('Y', strtotime($data['issue_date']));
            $path = "archives/{$data['company_id']}/{$year}";
            $data['file_path'] = $file->store($path, 'local');
            $data['file_type'] = $file->getClientOriginalExtension();
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
