<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SchoolRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Saas/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $baseSlug = Str::slug($data['school_name']) ?: 'ecole';
            $slug = $baseSlug;
            $suffix = 2;

            while (School::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $school = School::create([
                'name' => $data['school_name'],
                'slug' => $slug,
                'email' => $data['email'],
                'city' => $data['city'],
            ]);

            return $school->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_admin' => true,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Bienvenue ! Votre espace école est prêt.');
    }
}
