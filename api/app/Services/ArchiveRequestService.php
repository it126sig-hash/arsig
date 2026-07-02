<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ArchiveDownloadLog;
use App\Models\ArchiveDownloadRequest;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveRequestService
{
    public function listDownloadHistory(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = ArchiveDownloadLog::with([
                'archive.tags',
                'archive.category',
                'archive.company',
                'archive.pic.department.heads',
                'archive.floor',
                'archive.room',
                'archive.cabinet',
                'archive.cabinetSlot',
                'user',
            ])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['user_id'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($filters['pic_user_id'] ?? null, function ($q, $picUserId) {
                $q->whereHas('archive', fn ($sub) => $sub->where('pic_user_id', $picUserId));
            })
            ->when($filters['department_id'] ?? null, function ($q, $departmentId) {
                $q->whereHas('archive.pic', fn ($sub) => $sub->where('department_id', $departmentId));
            })
            ->when(array_key_exists('is_confidential', $filters) && $filters['is_confidential'] !== null, function ($q) use ($filters) {
                $q->whereHas('archive', fn ($sub) => $sub->where('is_confidential', $filters['is_confidential']));
            })
            ->whereHas('archive', fn ($q) => $q->visibleInHistoryTo($user))
            ->orderByDesc('created_at');

        return $query->paginate(15);
    }

    public function listForPic(User $user): Collection
    {
        $query = ArchiveDownloadRequest::with([
                'archive.pic.department.heads',
                'requester',
                'reviewer',
                'picApprover',
                'departmentApprover',
                'rejectedBy',
            ])
            ->orderByDesc('created_at');

        if (! in_array($user->role, ['root', 'admin'], true)) {
            $query->where(function ($query) use ($user) {
                $query->whereHas('archive', function ($q) use ($user) {
                    $q->where('pic_user_id', $user->id);
                })->orWhereHas('archive.pic.department.heads', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            });
        }

        return $query->get();
    }

    public function listForRequester(User $user): Collection
    {
        return ArchiveDownloadRequest::with([
                'archive.pic.department.heads',
                'requester',
                'reviewer',
                'picApprover',
                'departmentApprover',
                'rejectedBy',
            ])
            ->where('requester_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function canActOn(User $user, ArchiveDownloadRequest $request): bool
    {
        if (in_array($user->role, ['root', 'admin'], true)) {
            return true;
        }

        $request->loadMissing('archive.pic.department.heads');

        if ($request->approval_stage === 'pic') {
            return (int) $request->archive->pic_user_id === (int) $user->id;
        }

        if ($request->approval_stage === 'department') {
            return (bool) $request->archive->pic?->department?->heads->contains('id', $user->id);
        }

        return false;
    }

    public function approve(ArchiveDownloadRequest $request, User $reviewer): ArchiveDownloadRequest
    {
        return DB::transaction(function () use ($request, $reviewer) {
            $request->loadMissing('archive.pic.department.heads', 'requester');

            if ($request->requires_department_approval && $request->approval_stage === 'pic') {
                $departmentHeads = $request->archive->pic?->department?->heads ?? collect();

                if ($departmentHeads->isEmpty()) {
                    throw ValidationException::withMessages([
                        'department_head' => 'Kepala departemen belum diatur untuk departemen PIC. Atur kepala departemen terlebih dahulu.',
                    ]);
                }

                $request->update([
                    'approval_stage' => 'department',
                    'pic_approved_by_user_id' => $reviewer->id,
                    'pic_approved_at' => now(),
                    'reviewed_by_user_id' => $reviewer->id,
                ]);

                foreach ($departmentHeads as $departmentHead) {
                    $departmentHead->notify(new \App\Notifications\DepartmentApprovalRequestedNotification($request->archive, $request->requester));
                }

                return $request->load([
                    'archive.pic.department.heads',
                    'requester',
                    'reviewer',
                    'picApprover',
                    'departmentApprover',
                    'rejectedBy',
                ]);
            }

            $otp = (string) random_int(100000, 999999);
            
            $request->update([
                'status' => 'approved',
                'approval_stage' => 'completed',
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(15),
                'reviewed_by_user_id' => $reviewer->id,
                'pic_approved_by_user_id' => $request->pic_approved_by_user_id ?: ($request->approval_stage === 'pic' ? $reviewer->id : null),
                'pic_approved_at' => $request->pic_approved_at ?: ($request->approval_stage === 'pic' ? now() : null),
                'department_approved_by_user_id' => $request->requires_department_approval ? $reviewer->id : null,
                'department_approved_at' => $request->requires_department_approval ? now() : null,
                'is_verified' => false,
            ]);

            // Kirim Notifikasi Email
            if ($request->requester) {
                $request->requester->notify(new \App\Notifications\OtpApprovedNotification($request->archive, $otp));
            }

            // TODO: Dispatch SendFcmNotification Job (Native Push)
            // dispatch(new \App\Jobs\SendFcmNotification($request->requester, "Permintaan akses disetujui. Kode OTP Anda: {$otp}"));

            return $request->load([
                'archive.pic.department.heads',
                'requester',
                'reviewer',
                'picApprover',
                'departmentApprover',
                'rejectedBy',
            ]);
        });
    }

    public function reject(ArchiveDownloadRequest $request, User $reviewer): ArchiveDownloadRequest
    {
        $request->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewer->id,
            'rejected_by_user_id' => $reviewer->id,
            'rejected_at' => now(),
        ]);

        // TODO: Dispatch SendFcmNotification Job
        // dispatch(new \App\Jobs\SendFcmNotification($request->requester, "Permintaan akses arsip ditolak."));

        return $request->load([
            'archive.pic.department.heads',
            'requester',
            'reviewer',
            'picApprover',
            'departmentApprover',
            'rejectedBy',
        ]);
    }
}
