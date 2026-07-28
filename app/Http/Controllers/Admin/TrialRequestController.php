<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrialRequestController extends Controller
{
    public function update(Request $request, TrialRequest $trialRequest): RedirectResponse
    {
        $this->authorizeTrial($request, $trialRequest);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'min:6', 'max:30'],
            'preferred_date' => ['required', 'date'],
            'dance_role' => ['nullable', 'in:lead,follow'],
            'message' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);
        $trialRequest->update($data);
        return back()->with('success', 'La demande d’essai a été modifiée.');
    }

    public function destroy(Request $request, TrialRequest $trialRequest): RedirectResponse
    {
        $this->authorizeTrial($request, $trialRequest);
        $trialRequest->delete();
        return back()->with('success', 'La demande d’essai a été supprimée.');
    }

    private function authorizeTrial(Request $request, TrialRequest $trialRequest): void
    {
        abort_unless($trialRequest->school_id === $request->user()->school_id, 404);
    }
}
