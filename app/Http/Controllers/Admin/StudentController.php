<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function show(Request $request, User $student): Response
    {
        abort_unless($student->school_id === $request->user()->school_id && ! $student->is_admin, 404);

        $school = $request->user()->school;
        $enrollments = $school->enrollments()
            ->where(fn ($query) => $query->where('user_id', $student->id)->orWhere('email', $student->email))
            ->with(['course:id,title,style,level,teacher,location,day,time,start_date,end_date', 'invoice'])
            ->latest()
            ->get();
        $accepted = $enrollments->where('status', '!=', 'waitlist');
        $latest = $enrollments->first();

        return Inertia::render('Admin/Students/Show', [
            'student' => [
                ...$student->only('id', 'name', 'email', 'created_at', 'email_verified_at'),
                'phone' => $enrollments->firstWhere('phone', '!=', null)?->phone,
                'first_name' => $latest?->first_name,
                'last_name' => $latest?->last_name,
                'enrollments_count' => $enrollments->count(),
                'accepted_count' => $accepted->count(),
                'waitlist_count' => $enrollments->where('status', 'waitlist')->count(),
                'total_amount' => round((float) $accepted->sum('amount'), 2),
            ],
            'enrollments' => $enrollments,
            'trialRequests' => $school->trialRequests()->where('email', $student->email)->with('course:id,title,day,time,location')->latest()->get(),
        ]);
    }
}
