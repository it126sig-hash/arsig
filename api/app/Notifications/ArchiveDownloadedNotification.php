<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArchiveDownloadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Archive $archive,
        public readonly User $downloader
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Arsip Anda Telah Diunduh')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Arsip berikut yang Anda kelola telah diunduh:')
            ->line('Nama Arsip: ' . $this->archive->name)
            ->line('Diunduh Oleh: ' . $this->downloader->name . ' (' . $this->downloader->email . ')');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'archive_downloaded',
            'message' => $this->downloader->name . ' mengunduh arsip "' . $this->archive->name . '".',
            'archive_id' => $this->archive->id,
            'link' => '/file-explorer',
        ];
    }
}
