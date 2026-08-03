<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentReminderSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_reminders_enabled' => ['required', 'boolean'],
            'payment_reminder_steps' => ['required', 'array', 'min:1', 'max:50'],
            'payment_reminder_steps.*.delay_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'payment_reminder_steps.*.fee' => ['required', 'numeric', 'min:0', 'max:9999'],
        ]);

        $delays = collect($data['payment_reminder_steps'])->pluck('delay_days');
        if ($delays->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['payment_reminder_steps' => 'Chaque rappel doit avoir un délai différent.']);
        }

        $data['payment_reminder_steps'] = collect($data['payment_reminder_steps'])
            ->sortBy('delay_days')->values()->map(fn (array $step) => [
                'delay_days' => (int) $step['delay_days'],
                'fee' => round((float) $step['fee'], 2),
            ])->all();

        $request->user()->school->update($data);

        return back()->with('success', 'Les rappels automatiques de paiement ont été configurés.');
    }
}
