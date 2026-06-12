<?php

namespace App\Notifications;

use App\Models\ProductSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $productName = $this->subscription->product?->name ?? 'votre abonnement';

        return (new MailMessage)
            ->subject('Rappel de renouvellement — CYNA')
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line('Votre abonnement **'.$productName.'** sera renouvelé demain.')
            ->line('Date de facturation : **'.$this->subscription->next_billing->format('d/m/Y').'**')
            ->line('Montant : **'.$this->subscription->price.' €** ('.$this->subscription->cycle.')')
            ->line('Vous pouvez gérer vos abonnements depuis votre espace compte.');
    }
}
