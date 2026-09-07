<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'trial_confirmation' => fn () => $request->session()->get('trial_confirmation'),
                'waitlist' => fn () => $request->session()->get('waitlist'),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->only('id', 'name', 'email', 'is_admin'),
                    'school' => $request->user()->school?->only('id', 'name', 'slug', 'accent'),
                ] : null,
            ],
        ];
    }
}
