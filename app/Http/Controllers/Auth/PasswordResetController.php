<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response { return Inertia::render('Auth/ForgotPassword'); }
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'Si cette adresse existe, un lien de réinitialisation vient d’être envoyé.');
    }
    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', Rules\Password::defaults()]]);
        $resetUser = null;
        $status = Password::reset($data, function (User $user, string $password) use (&$resetUser) {
            $user->forceFill(['password' => $password])->setRememberToken(Str::random(60)); $user->save(); event(new PasswordReset($user));
            $resetUser = $user;
        });
        if ($status !== Password::PASSWORD_RESET) return back()->withErrors(['email' => __($status)]);
        if ($resetUser && ! $resetUser->is_admin && $resetUser->school) {
            return redirect()->route('students.login', $resetUser->school)->with('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        }
        return redirect()->route('login')->with('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
    }
}
