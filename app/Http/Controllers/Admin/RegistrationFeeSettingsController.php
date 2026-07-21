<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class RegistrationFeeSettingsController extends Controller {
    public function update(Request $request): RedirectResponse {
        $data = $request->validate(['registration_fee_enabled' => ['required', 'boolean'], 'registration_fee_name' => ['required', 'string', 'max:100'], 'registration_fee_amount' => ['required', 'numeric', 'min:0', 'max:9999']]);
        $request->user()->school->update($data);
        return back()->with('success', 'Les frais d’inscription ont été configurés.');
    }
}
