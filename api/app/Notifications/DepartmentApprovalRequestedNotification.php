<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepartmentApprovalRequestedNotification extends Notification implements ShouldQueue
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
        $frontendUrl = env('FRONTEND_URL', config('app.url'));

        return (new MailMessage)
            ->subject('Persetujuan Kepala Departemen untuk Arsip Confidential')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('PIC telah menyetujui permintaan akses arsip confidential berikut:')
            ->line('Nama Arsip: ' . $this->archive->name)
            ->line('Diminta Oleh: ' . $this->requester->name . ' (' . $this->requester->email . ')')
            ->action('Tinjau Persetujuan', $frontendUrl . '/approvals')
            ->line('Kode OTP baru akan dikirim setelah Anda menyetujui permintaan ini.');
    }
}
