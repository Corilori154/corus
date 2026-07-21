<?php

namespace App\Console\Commands;

use App\Services\PaymentReminderService;
use Illuminate\Console\Command;

class ProcessPaymentReminders extends Command
{
    protected $signature = 'invoices:send-reminders';
    protected $description = 'Envoie les rappels automatiques pour les factures échues et impayées';

    public function handle(PaymentReminderService $service): int
    {
        $count = $service->process();
        $this->info("{$count} rappel(s) de paiement envoyé(s).");
        return self::SUCCESS;
    }
}
