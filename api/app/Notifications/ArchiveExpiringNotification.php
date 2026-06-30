<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Archive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArchiveExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Archive $archive
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Arsip Segera Kadaluarsa')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Arsip berikut akan segera kadaluarsa:')
            ->line('Nama Arsip: ' . $this->archive->name)
            ->line('Tanggal Kadaluarsa: ' . $this->archive->expire_date?->toDateString());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'archive_expiring',
            'message' => 'Arsip "' . $this->archive->name . '" akan segera kadaluarsa pada ' . $this->archive->expire_date?->toDateString() . '.',
            'archive_id' => $this->archive->id,
            'link' => '/file-explorer',
        ];
    }
}
