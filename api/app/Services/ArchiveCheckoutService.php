<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\ArchiveCheckoutLog;
use App\Models\User;
use Illuminate\Support\Collection;

class ArchiveCheckoutService
{
    public function checkout(Archive $archive, array $data, User $actor): ArchiveCheckoutLog
    {
        if ($archive->is_checked_out) {
            throw new \Exception('Arsip sudah dalam status keluar');
        }

        $archive->update([
            'is_checked_out' => true,
            'checked_out_at' => now(),
        ]);

        return ArchiveCheckoutLog::create([
            'archive_id' => $archive->id,
            'actor_user_id' => $actor->id,
            'action' => 'checkout',
            'borrower_name' => $data['borrower_name'],
            'reason' => $data['reason'],
            'planned_return_date' => $data['planned_return_date'],
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function checkin(Archive $archive, User $actor): ArchiveCheckoutLog
    {
        if (!$archive->is_checked_out) {
            throw new \Exception('Arsip tidak dalam status keluar');
        }

        $archive->update([
            'is_checked_out' => false,
        ]);

        $lastCheckoutLog = ArchiveCheckoutLog::where('archive_id', $archive->id)
            ->where('action', 'checkout')
            ->whereNull('actual_return_date')
            ->latest()
            ->first();

        if ($lastCheckoutLog) {
            $lastCheckoutLog->update([
                'actual_return_date' => now(),
            ]);
        }

        return ArchiveCheckoutLog::create([
            'archive_id' => $archive->id,
            'actor_user_id' => $actor->id,
            'action' => 'checkin',
            'created_at' => now(),
        ]);
    }

    public function getHistory(Archive $archive): Collection
    {
        return ArchiveCheckoutLog::where('archive_id', $archive->id)
            ->with('actorUser')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
