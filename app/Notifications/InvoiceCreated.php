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
        $invoices = $invoice->enrollment->invoices()->with('payments')->get();
        $mail = (new MailMessage)
            ->subject(($invoices->count() > 1 ? 'Vos factures' : "Facture {$invoice->number}")." — {$invoice->school->name}")
            ->replyTo($invoice->school->email, $invoice->school->name)
            ->greeting("Bonjour {$invoice->enrollment->first_name},")
            ->line("Votre inscription au cours {$invoice->enrollment->course?->title} a bien été enregistrée.");

        if ($invoices->count() > 1) {
            $mail->line("Votre plan de paiement comprend {$invoices->count()} factures. Elles sont toutes disponibles dès maintenant :");
        }

        foreach ($invoices as $scheduledInvoice) {
            $url = URL::temporarySignedRoute('invoices.public', now()->addMonths(6), ['invoice' => $scheduledInvoice]);
            $label = $invoices->count() > 1
                ? "Facture {$scheduledInvoice->installment_number}/{$scheduledInvoice->installment_count} — {$scheduledInvoice->number}"
                : "Facture {$scheduledInvoice->number}";

            $mail->line("[{$label}]({$url}) — ".number_format((float) $scheduledInvoice->amount, 2, ',', ' ').' CHF — échéance au '.$scheduledInvoice->due_at->format('d.m.Y'));
        }

        return $mail
            ->line('Chaque facture contient une section Swiss QR compatible avec les applications bancaires suisses.')
            ->salutation("L’équipe de {$invoice->school->name}");
    }
}
