<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TermsSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['terms_and_conditions' => ['required', 'string', 'max:50000']]);
        $request->user()->school->update($data);
        return back()->with('success', 'Les conditions générales ont été enregistrées.');
    }
}
