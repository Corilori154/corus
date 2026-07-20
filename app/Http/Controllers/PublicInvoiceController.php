<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicInvoiceController extends Controller
{
    public function show(Request $request, Invoice $invoice, InvoiceService $service): View
    {
        abort_unless($request->hasValidSignature(), 403);
        $invoice->load(['school', 'enrollment.course', 'enrollment.invoices.payments', 'payments']);
        $paymentPart = null; $qrError = null;
        try { if ($invoice->balance > 0) $paymentPart = $service->paymentPart($invoice); }
        catch (\Throwable $exception) { $qrError = $exception->getMessage(); }
        return view('invoices.show', compact('invoice', 'paymentPart', 'qrError'));
    }
}
