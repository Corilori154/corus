<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdministratorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->school->users()->create([...$data, 'is_admin' => true]);

        return back()->with('success', 'Le compte administrateur a été créé.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('success', 'Les informations de votre compte ont été mises à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Votre mot de passe a été modifié.');
    }

    public function destroy(Request $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->school_id === $request->user()->school_id && $administrator->is_admin, 404);
        abort_if($administrator->is($request->user()), 422, 'Vous ne pouvez pas supprimer votre propre compte.');
        abort_if($request->user()->school->users()->where('is_admin', true)->count() <= 1, 422, 'L’école doit conserver au moins un administrateur.');

        $administrator->delete();

        return back()->with('success', 'Le compte administrateur a été supprimé.');
    }

    public function update(Request $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->school_id === $request->user()->school_id && $administrator->is_admin, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($administrator->id)],
        ]);
        $administrator->update($data);
        return back()->with('success', 'Le compte administrateur a été modifié.');
    }
}
