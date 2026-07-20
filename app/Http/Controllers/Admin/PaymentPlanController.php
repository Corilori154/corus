<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentPlanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'installment_count' => ['required', 'integer', 'min:2', 'max:24'],
            'schedule_mode' => ['required', 'in:evenly_spaced,monthly_end'],
            'adjustment_direction' => ['required', 'in:fee,discount'],
            'adjustment_mode' => ['required', 'in:fixed,percentage'],
            'adjustment_value' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);
        $request->user()->school->paymentPlans()->create([...$data, 'is_active' => true]);
        return back()->with('success', 'Le plan de paiement a été créé.');
    }

    public function destroy(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        abort_unless($paymentPlan->school_id === $request->user()->school_id, 404);
        $paymentPlan->delete();
        return back()->with('success', 'Le plan de paiement a été supprimé.');
    }
}
