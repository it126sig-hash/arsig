<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Archive $archive,
        public readonly User $requester
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Gunakan FRONTEND_URL dari .env, atau fallback ke APP_URL
        $frontendUrl = env('FRONTEND_URL', config('app.url'));

        return (new MailMessage)
            ->subject('Permintaan Kode OTP Arsip')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Ada permintaan akses baru untuk arsip berikut:')
            ->line('Nama Arsip: ' . $this->archive->name)
            ->line('Diminta Oleh: ' . $this->requester->name . ' (' . $this->requester->email . ')')
            ->action('Tinjau Permintaan (Approve/Reject)', $frontendUrl . '/approvals')
            ->line('Mohon segera tinjau permintaan tersebut.');
    }
}
