<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InvoiceCreated extends Notification
{
    use Queueable;
    public function __construct(private readonly Invoice $invoice) {}
    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice->loadMissing(['school', 'enrollment.course']);
        $url = URL::temporarySignedRoute('invoices.public', now()->addMonths(6), ['invoice' => $invoice]);
        return (new MailMessage)
            ->subject("Facture {$invoice->number} — {$invoice->school->name}")
            ->replyTo($invoice->school->email, $invoice->school->name)
            ->greeting("Bonjour {$invoice->enrollment->first_name},")
            ->line("Votre inscription au cours {$invoice->enrollment->course?->title} a bien été enregistrée.")
            ->line(($invoice->installment_count > 1 ? "Échéance {$invoice->installment_number}/{$invoice->installment_count} : " : 'Montant de la facture : ').number_format((float) $invoice->amount, 2, ',', ' ').' CHF')
            ->line('Solde à payer : '.number_format((float) $invoice->balance, 2, ',', ' ').' CHF')
            ->line('Échéance : '.$invoice->due_at->format('d.m.Y'))
            ->action('Consulter et payer ma facture', $url)
            ->line('La facture contient une section Swiss QR compatible avec les applications bancaires suisses.')
            ->salutation("L’équipe de {$invoice->school->name}");
    }
}
