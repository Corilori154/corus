<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'billing_name' => ['required', 'string', 'max:70'],
            'billing_street' => ['required', 'string', 'max:70'],
            'billing_house_number' => ['required', 'string', 'max:20'],
            'billing_postal_code' => ['required', 'string', 'max:16'],
            'billing_city' => ['required', 'string', 'max:35'],
            'billing_country' => ['required', 'in:CH,LI'],
            'billing_iban' => ['required', 'string', 'max:34', 'regex:/^(CH|LI)[0-9A-Z ]{19,32}$/i', function (string $attribute, mixed $value, \Closure $fail) {
                $iban = strtoupper(preg_replace('/\s+/', '', (string) $value));
                $iid = (int) substr($iban, 4, 5);
                if ($iid >= 30000 && $iid <= 31999) {
                    $fail('Utilisez un IBAN classique pour ce mode de facture, pas un QR-IBAN.');
                }
                try { \Sprain\SwissQrBill\DataGroup\Element\CreditorInformation::create($iban); }
                catch (\Throwable) { $fail('L’IBAN saisi n’est pas valide.'); }
            }],
            'invoice_prefix' => ['required', 'alpha_num', 'max:12'],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);
        $data['billing_iban'] = strtoupper(preg_replace('/\s+/', '', $data['billing_iban']));
        $request->user()->school->update($data);

        return back()->with('success', 'Les coordonnées de facturation suisse ont été enregistrées.');
    }
}
