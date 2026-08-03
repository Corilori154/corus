<?php

namespace App\Services;

use App\Models\Invoice;
use App\Notifications\PaymentReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PaymentReminderService
{
    public function process(): int
    {
        $count = 0;
        Invoice::query()->with(['school', 'payments', 'enrollment'])->whereDate('due_at', '<', today())
            ->whereHas('school', fn ($query) => $query->where('payment_reminders_enabled', true))
            ->chunkById(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    if ($this->sendIfDue($invoice->id)) $count++;
                }
            });
        return $count;
    }

    private function sendIfDue(int $invoiceId): bool
    {
        return DB::transaction(function () use ($invoiceId) {
            $invoice = Invoice::with(['school', 'payments', 'enrollment'])->lockForUpdate()->find($invoiceId);
            if (! $invoice || ! $invoice->school->payment_reminders_enabled || $invoice->balance <= 0) return false;

            $school = $invoice->school;
            $step = $school->paymentReminderSteps()[$invoice->reminder_count] ?? null;
            if (! $step) return false;
            $dueAt = $invoice->due_at->copy()->addDays((int) $step['delay_days'])->startOfDay();
            if (now()->lt($dueAt)) return false;

            $fee = (float) $step['fee'];
            $invoice->update([
                'amount' => round((float) $invoice->amount + $fee, 2),
                'reminder_fees_total' => round((float) $invoice->reminder_fees_total + $fee, 2),
                'reminder_count' => $invoice->reminder_count + 1,
                'last_reminder_at' => now(),
            ]);
            $invoice->refresh()->load(['school', 'payments', 'enrollment']);
            Notification::route('mail', $invoice->enrollment->email)->notify(new PaymentReminder($invoice, $fee));
            return true;
        });
    }
}
