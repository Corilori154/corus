<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class ContactButtonSettingsController extends Controller {
    public function update(Request $request): RedirectResponse {
        $data = $request->validate([
            'contact_button_label' => ['required', 'string', 'max:80'],
            'contact_button_url' => ['nullable', 'string', 'max:1000', 'regex:/^(https?:\/\/|mailto:|tel:)/i'],
        ], ['contact_button_url.regex' => 'Utilisez une URL https://, une adresse mailto: ou un numéro tel:.']);
        $request->user()->school->update($data);
        return back()->with('success', 'Le bouton de contact a été configuré.');
    }
}
