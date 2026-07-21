<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Invoice;
use Sprain\SwissQrBill as QrBill;
use Sprain\SwissQrBill\PaymentPart\Output\DisplayOptions;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Carbon\CarbonImmutable;

class InvoiceService
{
    public function createFor(Enrollment $enrollment): Invoice
    {
        $school = $enrollment->school;
        $count = max(1, (int) $enrollment->installment_count);
        $feeCents = (int) round((float) $enrollment->registration_fee_amount * 100);
        $tuitionCents = max(0, (int) round((float) $enrollment->amount * 100) - $feeCents);
        $baseCents = intdiv($tuitionCents, $count);
        $remainder = $tuitionCents % $count;
        $today = CarbonImmutable::today();
        $plan = $enrollment->paymentPlan;
        $courseEnd = CarbonImmutable::parse($enrollment->course->end_date)->startOfDay();
        $spanDays = max($count - 1, $today->diffInDays($courseEnd, false));
        $firstInvoice = null;

        for ($index = 0; $index < $count; $index++) {
            $dueAt = $count === 1
                ? $today->addDays($school->invoice_due_days ?? 30)
                : ($index === 0
                    ? $today
                    : ($plan?->schedule_mode === 'monthly_end'
                        ? $today->addMonthsNoOverflow($index)->endOfMonth()
                        : $today->addDays((int) round($spanDays * $index / ($count - 1)))));
            $invoice = $school->invoices()->create([
                'enrollment_id' => $enrollment->id,
                'installment_number' => $index + 1,
                'installment_count' => $count,
                'amount' => ($baseCents + ($index < $remainder ? 1 : 0) + ($index === 0 ? $feeCents : 0)) / 100,
                'currency' => 'CHF', 'issued_at' => $today, 'due_at' => $dueAt,
            ]);
            $invoice->update(['number' => sprintf('%s-%s-%06d', strtoupper($school->invoice_prefix ?: 'FAC'), now()->format('Y'), $invoice->id)]);
            $firstInvoice ??= $invoice;
        }

        return $firstInvoice;
    }

    public function paymentPart(Invoice $invoice): string
    {
        $school = $invoice->school;
        if (! $school->hasCompleteBillingSettings()) {
            throw new \RuntimeException('Les coordonnées de facturation de l’école sont incomplètes.');
        }

        $bill = QrBill\QrBill::create();
        $bill->setCreditor(QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
            $school->billing_name, $school->billing_street, $school->billing_house_number,
            $school->billing_postal_code, $school->billing_city, strtoupper($school->billing_country)
        ));
        $bill->setCreditorInformation(QrBill\DataGroup\Element\CreditorInformation::create(preg_replace('/\s+/', '', $school->billing_iban)));
        $bill->setPaymentAmountInformation(QrBill\DataGroup\Element\PaymentAmountInformation::create('CHF', (float) $invoice->balance));
        $bill->setPaymentReference(QrBill\DataGroup\Element\PaymentReference::create(QrBill\DataGroup\Element\PaymentReference::TYPE_NON));
        $bill->setAdditionalInformation(QrBill\DataGroup\Element\AdditionalInformation::create('Facture '.$invoice->number));

        $options = (new DisplayOptions())->setPrintable(false)->setDisplayTextDownArrows(false)->setDisplayScissors(true);

        return (new HtmlOutput($bill, 'fr'))->setDisplayOptions($options)->getPaymentPart();
    }
}
