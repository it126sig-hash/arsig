<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

use App\Models\ArchiveLocationLog;
use Illuminate\Pagination\LengthAwarePaginator;

class ArchiveService
{
    public function list(
        ?int $companyId = null,
        ?int $categoryId = null,
        ?string $q = null,
        ?string $archiveType = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        array $tagIds = [],
        ?bool $filterExpiring = null,
        ?bool $filterBorrowed = null,
        ?bool $filterInCabinet = null,
        ?string $status = 'active'
    ) {
        $archives = Archive::query()
            ->with(['tags', 'accessDepartments', 'accessUsers', 'category', 'company', 'pic.department.heads', 'floor', 'room', 'cabinet', 'cabinetSlot', 'lastCheckout'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($archiveType, fn ($q) => $q->where('archive_type', $archiveType))
            ->when($dateFrom, fn ($q) => $q->whereDate('issue_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('issue_date', '<=', $dateTo))
            ->when($filterExpiring, function ($query) {
                $query->whereNotNull('expire_date')
                    ->where(function ($sub) {
                        $sub->whereDate('reminder_date', '<=', \Carbon\Carbon::now())
                            ->orWhereDate('expire_date', '<=', \Carbon\Carbon::now()->addDays(30));
                    });
            })
            ->when($filterBorrowed, fn ($q) => $q->where('is_checked_out', true))
            ->when($filterInCabinet, fn ($q) => $q->where('is_checked_out', false))
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
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        $user = Auth::user();

        return $archives->map(function (Archive $archive) use ($user) {
            $canViewLocation = $user ? $user->can('viewLocation', $archive) : false;
            $canMoveLocation = $user ? $user->can('moveLocation', $archive) : false;

            $archive->setAttribute('can_view_location', $canViewLocation);
            $archive->setAttribute('can_move_location', $canMoveLocation);

            if ($archive->is_confidential && ! $canViewLocation) {
                $archive->setRelation('floor', null);
                $archive->setRelation('room', null);
                $archive->setRelation('cabinet', null);
                $archive->setRelation('cabinetSlot', null);
                $archive->floor_id = null;
                $archive->room_id = null;
                $archive->cabinet_id = null;
                $archive->cabinet_slot_id = null;
            }

            return $archive;
        });
    }

    public function store(array $data, ?UploadedFile $file): Archive
    {
        $data['is_confidential'] = (bool) ($data['is_confidential'] ?? false);

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
        $data['is_confidential'] = (bool) ($data['is_confidential'] ?? false);

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

    public function moveLocation(Archive $archive, array $data): Archive
    {
        // Record log
        ArchiveLocationLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'old_floor_id' => $archive->floor_id,
            'old_room_id' => $archive->room_id,
            'old_cabinet_id' => $archive->cabinet_id,
            'old_cabinet_slot_id' => $archive->cabinet_slot_id,
            'new_floor_id' => $data['new_floor_id'],
            'new_room_id' => $data['new_room_id'],
            'new_cabinet_id' => $data['new_cabinet_id'],
            'new_cabinet_slot_id' => $data['new_cabinet_slot_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Update archive
        $archive->update([
            'floor_id' => $data['new_floor_id'],
            'room_id' => $data['new_room_id'],
            'cabinet_id' => $data['new_cabinet_id'],
            'cabinet_slot_id' => $data['new_cabinet_slot_id'] ?? null,
        ]);

        return $archive->load(['floor', 'room', 'cabinet', 'cabinetSlot']);
    }

    public function getLocationHistories(User $user, array $filters = []): LengthAwarePaginator
    {
        return ArchiveLocationLog::query()
            ->with([
                'archive',
                'movedBy',
                'oldFloor', 'oldRoom', 'oldCabinet', 'oldCabinetSlot',
                'newFloor', 'newRoom', 'newCabinet', 'newCabinetSlot'
            ])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->whereHas('archive', function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('file_number', 'like', "%{$q}%");
                });
            })
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->whereHas('archive', fn ($q) => $q->visibleInHistoryTo($user))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function toggleStatus(Archive $archive): Archive
    {
        $archive->update([
            'status' => $archive->status === 'active' ? 'inactive' : 'active'
        ]);

        return $archive->refresh();
    }

    public function moveCategory(Archive $archive, int $companyId, int $categoryId): Archive
    {
        $archive->update([
            'company_id' => $companyId,
            'category_id' => $categoryId
        ]);

        return $archive->load(['category', 'company']);
    }
}
