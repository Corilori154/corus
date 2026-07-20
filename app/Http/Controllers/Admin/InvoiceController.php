<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Notifications\InvoiceCreated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function show(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 404);
        $invoice->load(['school', 'enrollment.course', 'enrollment.invoices.payments', 'payments.recorder:id,name']);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'documentUrl' => route('admin.invoices.document', $invoice),
        ]);
    }

    public function document(Request $request, Invoice $invoice, InvoiceService $service): View
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 404);
        $invoice->load(['school', 'enrollment.course', 'enrollment.invoices.payments', 'payments']);
        $paymentPart = null;
        $qrError = null;
        try { if ($invoice->balance > 0) $paymentPart = $service->paymentPart($invoice); }
        catch (\Throwable $exception) { $qrError = $exception->getMessage(); }

        return view('invoices.show', compact('invoice', 'paymentPart', 'qrError'));
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 404);
        if ($invoice->balance > 0) {
            $invoice->payments()->create(['amount' => $invoice->balance, 'paid_on' => today(), 'method' => 'other', 'note' => 'Paiement complet enregistré', 'recorded_by' => $request->user()->id]);
        }
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', "La facture {$invoice->number} est marquée comme payée.");
    }

    public function payment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in(['bank_transfer', 'cash', 'card', 'twint', 'other'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($invoice, $data, $request) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ((float) $data['amount'] > $locked->balance) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Le paiement ne peut pas dépasser le solde de '.number_format($locked->balance, 2, ',', ' ').' CHF.']);
            }
            $locked->payments()->create([...$data, 'recorded_by' => $request->user()->id]);
            $locked->refresh();
            $locked->update(['status' => $locked->balance <= 0 ? 'paid' : 'open', 'paid_at' => $locked->balance <= 0 ? now() : null]);
        });

        return back()->with('success', 'Le paiement partiel a été enregistré et le solde de la facture a été recalculé.');
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 404);
        $invoice->load('enrollment');
        Notification::route('mail', $invoice->enrollment->email)->notify(new InvoiceCreated($invoice));
        return back()->with('success', "La facture {$invoice->number} a été envoyée à {$invoice->enrollment->email}.");
    }
}
