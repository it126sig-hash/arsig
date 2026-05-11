<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ArchiveDownloadRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArchiveRequestService
{
    public function listForPic(User $user): Collection
    {
        $query = ArchiveDownloadRequest::with(['archive', 'requester', 'reviewer'])
            ->orderByDesc('created_at');

        if ($user->role !== 'admin') {
            $query->whereHas('archive', function ($q) use ($user) {
                $q->where('pic_user_id', $user->id);
            });
        }

        return $query->get();
    }

    public function approve(ArchiveDownloadRequest $request, User $reviewer): ArchiveDownloadRequest
    {
        return DB::transaction(function () use ($request, $reviewer) {
            $otp = (string) random_int(100000, 999999);
            
            $request->update([
                'status' => 'approved',
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(15),
                'reviewed_by_user_id' => $reviewer->id,
                'is_verified' => false,
            ]);

            // Kirim Notifikasi Email
            if ($request->requester) {
                $request->requester->notify(new \App\Notifications\OtpApprovedNotification($request->archive, $otp));
            }

            // TODO: Dispatch SendFcmNotification Job (Native Push)
            // dispatch(new \App\Jobs\SendFcmNotification($request->requester, "Permintaan akses disetujui. Kode OTP Anda: {$otp}"));

            return $request->load(['archive', 'requester', 'reviewer']);
        });
    }

    public function reject(ArchiveDownloadRequest $request, User $reviewer): ArchiveDownloadRequest
    {
        $request->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        // TODO: Dispatch SendFcmNotification Job
        // dispatch(new \App\Jobs\SendFcmNotification($request->requester, "Permintaan akses arsip ditolak."));

        return $request->load(['archive', 'requester', 'reviewer']);
    }
}
