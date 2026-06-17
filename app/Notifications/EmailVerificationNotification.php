<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = (int) config('cyna.email_verification_expire_hours', 24);
        $baseUrl = rtrim((string) config('cyna.frontend_url'), '/');
        $verifyUrl = $baseUrl.'/confirmer-email.php?id='.$notifiable->id.'&token='.$notifiable->token_confirmation;

        return (new MailMessage)
            ->subject('Confirmez votre inscription — CYNA')
            ->greeting('Bonjour '.$notifiable->prenom.' !')
            ->line('Merci de vous être inscrit sur CYNA.')
            ->line('Pour activer votre compte, cliquez sur le bouton ci-dessous. Ce lien est unique et ne peut être utilisé qu\'une seule fois.')
            ->action('Confirmer mon inscription', $verifyUrl)
            ->line('Ce lien expire dans '.$hours.' heure'.($hours > 1 ? 's' : '').'.')
            ->line('Si vous n\'avez pas créé de compte sur CYNA, ignorez cet email.');
    }
}
