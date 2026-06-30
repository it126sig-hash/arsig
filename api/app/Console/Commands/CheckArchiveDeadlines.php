<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Archive;
use App\Models\ArchiveCheckoutLog;
use App\Notifications\ArchiveExpiringNotification;
use App\Notifications\ArchiveOverdueNotification;
use Illuminate\Console\Command;

class CheckArchiveDeadlines extends Command
{
    protected $signature = 'archives:check-deadlines';

    protected $description = 'Notify PIC of archives expiring today and overdue checkouts (first day overdue).';

    public function handle(): int
    {
        Archive::whereDate('reminder_date', today())
            ->whereNotNull('pic_user_id')
            ->with('pic')
            ->each(function (Archive $archive) {
                $archive->pic?->notify(new ArchiveExpiringNotification($archive));
            });

        ArchiveCheckoutLog::where('action', 'checkout')
            ->whereNull('actual_return_date')
            ->whereDate('planned_return_date', today()->subDay())
            ->with('archive.pic', 'actorUser')
            ->each(function (ArchiveCheckoutLog $log) {
                if (! $log->archive) {
                    return;
                }

                $log->actorUser?->notify(new ArchiveOverdueNotification($log->archive, $log));

                if ($log->archive->pic && (int) $log->archive->pic_user_id !== (int) $log->actor_user_id) {
                    $log->archive->pic->notify(new ArchiveOverdueNotification($log->archive, $log));
                }
            });

        return self::SUCCESS;
    }
}
