<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentReminderSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_reminders_enabled' => ['required', 'boolean'],
            'payment_reminder_delay_days' => ['required', 'integer', 'min:0', 'max:365'],
            'payment_reminder_interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'payment_reminder_max_count' => ['required', 'integer', 'min:1', 'max:10'],
            'payment_reminder_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
        ]);
        $request->user()->school->update($data);
        return back()->with('success', 'Les rappels automatiques de paiement ont été configurés.');
    }
}
