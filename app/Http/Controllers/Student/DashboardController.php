<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, School $school): Response
    {
        $user = $request->user();
        $enrollments = $school->enrollments()
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('email', $user->email))
            ->with([
                'course:id,title,style,level,day,time,teacher,location,image',
                'invoices.payments',
            ])
            ->latest()
            ->get();

        $trials = $school->trialRequests()
            ->where('email', $user->email)
            ->with('course:id,title,day,time,location')
            ->latest()
            ->get();

        return Inertia::render('Student/Dashboard', [
            'school' => $school->only('id', 'name', 'slug', 'city', 'email', 'phone', 'accent'),
            'enrollments' => $enrollments,
            'trials' => $trials,
        ]);
    }
}
