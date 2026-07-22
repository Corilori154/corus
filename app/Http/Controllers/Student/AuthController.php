<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(Request $request, School $school): Response|RedirectResponse
    {
        if ($request->user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()) {
            return redirect()->route('student.dashboard', $request->user()->school);
        }

        return Inertia::render('Student/Login', [
            'school' => $school->only('name', 'slug', 'city', 'accent'),
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            ...$credentials,
            'school_id' => $school->id,
            'is_admin' => false,
        ], $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Ces identifiants élève sont incorrects pour cette école.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('student.dashboard', $school));
    }

    public function destroy(Request $request, School $school): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('courses.index', $school);
    }
}
