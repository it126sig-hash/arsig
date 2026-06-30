<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Archive;
use App\Models\ArchiveCheckoutLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArchiveOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Archive $archive,
        public readonly ArchiveCheckoutLog $checkoutLog
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Arsip Terlambat Dikembalikan')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Arsip berikut sudah melewati batas waktu pengembalian:')
            ->line('Nama Arsip: ' . $this->archive->name)
            ->line('Rencana Kembali: ' . $this->checkoutLog->planned_return_date?->toDateString())
            ->line('Peminjam: ' . $this->checkoutLog->borrower_name);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'archive_overdue',
            'message' => 'Arsip "' . $this->archive->name . '" terlambat dikembalikan (rencana: ' . $this->checkoutLog->planned_return_date?->toDateString() . ').',
            'archive_id' => $this->archive->id,
            'link' => '/file-explorer',
        ];
    }
}
