<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');

        abort_unless(
            $request->user()
            && ! $request->user()->is_admin
            && $request->user()->school?->is_active
            && $request->user()->school_id === $school?->id,
            403,
        );

        return $next($request);
    }
}
