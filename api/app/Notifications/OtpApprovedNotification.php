<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Archive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Archive $archive,
        public readonly string $otpCode
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Akses Arsip Anda')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Permintaan akses Anda untuk arsip berikut telah disetujui:')
            ->line('Nama Arsip: ' . $this->archive->name)
            ->line('Kode OTP Anda adalah:')
            ->line($this->otpCode)
            ->line('Kode ini berlaku selama 15 menit.')
            ->line('Silakan gunakan kode ini untuk memverifikasi akses Anda di aplikasi.')
            ->line('Jika Anda tidak merasa meminta kode ini, abaikan email ini.');
    }
}
