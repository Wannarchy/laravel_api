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
        $url = config('app.url').'/api/auth/verify-email';

        return (new MailMessage)
            ->subject('Vérification de votre email — CYNA')
            ->line('Merci de vous être inscrit sur CYNA.')
            ->line('Utilisez le token ci-dessous pour confirmer votre adresse email :')
            ->line('**'.$notifiable->token_confirmation.'**')
            ->action('Vérifier mon email', $url.'?id='.$notifiable->id.'&token='.$notifiable->token_confirmation)
            ->line('Si vous n\'avez pas créé de compte, ignorez cet email.');
    }
}
