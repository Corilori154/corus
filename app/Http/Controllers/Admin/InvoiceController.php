<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Enrollment;
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
    public function store(Request $request): RedirectResponse
    {
        $school = $request->user()->school;
        $data = $request->validate([
            'enrollment_id' => ['required', Rule::exists('enrollments', 'id')->where('school_id', $school->id)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
        ]);

        $invoice = DB::transaction(function () use ($data, $school) {
            $enrollment = Enrollment::where('school_id', $school->id)->lockForUpdate()->findOrFail($data['enrollment_id']);
            $nextInstallment = ((int) $enrollment->invoices()->max('installment_number')) + 1;
            $invoice = $school->invoices()->create([
                'enrollment_id' => $enrollment->id,
                'installment_number' => $nextInstallment,
                'installment_count' => $nextInstallment,
                'amount' => $data['amount'],
                'currency' => 'CHF',
                'issued_at' => $data['issued_at'],
                'due_at' => $data['due_at'],
                'status' => 'open',
            ]);
            $invoice->update(['number' => sprintf('%s-%s-%06d', strtoupper($school->invoice_prefix ?: 'FAC'), now()->format('Y'), $invoice->id)]);
            $enrollment->invoices()->update(['installment_count' => $nextInstallment]);
            return $invoice;
        });

        return redirect()->route('admin.invoices.show', $invoice)->with('success', "La facture {$invoice->number} a été créée.");
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
        ]);
        if ((float) $data['amount'] < $invoice->paid_amount) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Le montant ne peut pas être inférieur au total déjà encaissé.']);
        }
        $invoice->update($data);
        $invoice->refresh()->update(['status' => $invoice->balance <= 0 ? 'paid' : 'open', 'paid_at' => $invoice->balance <= 0 ? now() : null]);
        return back()->with('success', 'La facture a été modifiée.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);
        $invoice->delete();
        return redirect()->route('admin.dashboard', ['section' => 'invoices'])->with('success', 'La facture et ses paiements ont été supprimés.');
    }

    public function updatePayment(Request $request, Invoice $invoice, InvoicePayment $payment): RedirectResponse
    {
        $this->authorizePayment($request, $invoice, $payment);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'paid_on' => ['required', 'date'], 'method' => ['required', Rule::in(['bank_transfer', 'cash', 'card', 'twint', 'other'])], 'note' => ['nullable', 'string', 'max:500']]);
        $otherPaid = (float) $invoice->payments()->where('id', '!=', $payment->id)->sum('amount');
        if ($otherPaid + (float) $data['amount'] > (float) $invoice->amount) throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Le total des paiements dépasserait le montant de la facture.']);
        $payment->update($data); $this->syncStatus($invoice);
        return back()->with('success', 'Le paiement a été modifié.');
    }

    public function destroyPayment(Request $request, Invoice $invoice, InvoicePayment $payment): RedirectResponse
    {
        $this->authorizePayment($request, $invoice, $payment);
        $payment->delete(); $this->syncStatus($invoice);
        return back()->with('success', 'Le paiement a été supprimé.');
    }
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

    private function authorizeInvoice(Request $request, Invoice $invoice): void { abort_unless($invoice->school_id === $request->user()->school_id, 404); }
    private function authorizePayment(Request $request, Invoice $invoice, InvoicePayment $payment): void { $this->authorizeInvoice($request, $invoice); abort_unless($payment->invoice_id === $invoice->id, 404); }
    private function syncStatus(Invoice $invoice): void { $invoice->refresh(); $invoice->update(['status' => $invoice->balance <= 0 ? 'paid' : 'open', 'paid_at' => $invoice->balance <= 0 ? now() : null]); }
}
