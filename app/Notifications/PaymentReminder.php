<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PaymentReminder extends Notification
{
    use Queueable;

    public function __construct(private readonly Invoice $invoice, private readonly float $fee) {}
    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice->loadMissing(['school', 'enrollment']);
        $url = URL::temporarySignedRoute('invoices.public', now()->addMonths(6), ['invoice' => $invoice]);
        $mail = (new MailMessage)
            ->subject("Rappel de paiement — facture {$invoice->number}")
            ->replyTo($invoice->school->email, $invoice->school->name)
            ->greeting("Bonjour {$invoice->enrollment->first_name},")
            ->line("La facture {$invoice->number}, échue le {$invoice->due_at->format('d.m.Y')}, présente encore un solde de ".number_format($invoice->balance, 2, ',', ' ').' CHF.');
        if ($this->fee > 0) $mail->line('Des frais de rappel de '.number_format($this->fee, 2, ',', ' ').' CHF ont été ajoutés.');
        return $mail->action('Consulter et payer la facture', $url)
            ->line('Si votre paiement a déjà été effectué, veuillez ne pas tenir compte de ce message.')
            ->salutation("L’équipe de {$invoice->school->name}");
    }
}
