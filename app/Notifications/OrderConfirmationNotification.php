<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing(['items.product']);

        $baseUrl = rtrim((string) config('cyna.frontend_url'), '/');
        $orderUrl = $baseUrl.'/confirmation.php?order_id='.$this->order->id;

        $mail = (new MailMessage)
            ->subject('Confirmation de commande #'.$this->order->id.' — CYNA')
            ->greeting('Bonjour '.$notifiable->prenom.',')
            ->line('Merci pour votre commande. Votre paiement a bien été enregistré.')
            ->line('**Commande n° '.$this->order->id.'** — '.$this->order->created_at?->format('d/m/Y H:i'));

        foreach ($this->order->items as $item) {
            $productName = $item->product?->name ?? 'Service CYNA';
            $cycleLabel = $item->cycle === 'yearly' ? 'Annuel' : 'Mensuel';

            $mail->line(
                '• **'.$productName.'** ('.$cycleLabel.') — '.$this->formatMoney((float) $item->price)
            );
        }

        if ((float) $this->order->promo_discount > 0) {
            $promoLine = 'Réduction';
            if ($this->order->promo_code) {
                $promoLine .= ' ('.$this->order->promo_code.')';
            }
            $mail->line($promoLine.' : -'.$this->formatMoney((float) $this->order->promo_discount));
        }

        if ((float) $this->order->tax_amount > 0) {
            $mail->line('TVA incluse : '.$this->formatMoney((float) $this->order->tax_amount));
        }

        $mail->line('**Total payé : '.$this->formatMoney((float) $this->order->total).'**');

        if ($this->order->billing_name) {
            $mail->line('Facturation : **'.$this->order->billing_name.'**');
        }

        if ($this->order->billing_address) {
            $mail->line($this->order->billing_address);
        }

        if ($this->order->card_last4) {
            $brand = $this->order->payment_brand ? strtoupper($this->order->payment_brand).' ' : '';
            $mail->line('Paiement : '.$brand.'•••• '.$this->order->card_last4);
        }

        return $mail
            ->action('Voir ma commande', $orderUrl)
            ->line('Vous pouvez aussi consulter vos commandes et abonnements depuis votre espace compte.');
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' €';
    }
}
