<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AdminLoginOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $expireMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Code de connexion administrateur — CYNA')
            ->greeting('Bonjour '.$notifiable->prenom.' !')
            ->line('Une tentative de connexion à l\'espace administrateur CYNA a été détectée.')
            ->line('Votre code de vérification est :')
            ->line(new HtmlString('<p style="font-size:28px;font-weight:700;letter-spacing:6px;margin:16px 0;">'.e($this->code).'</p>'))
            ->line('Ce code est valable '.$this->expireMinutes.' minutes.')
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail et contactez un responsable.');
    }
}
