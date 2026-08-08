<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DanceCourse;
use App\Services\WaitlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function update(Request $request, User $student): RedirectResponse
    {
        $this->authorizeStudent($request, $student);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+() .\-]+$/'],
        ]);

        DB::transaction(function () use ($request, $student, $data) {
            $oldEmail = $student->email;
            $request->user()->school->enrollments()
                ->where(fn ($query) => $query->where('user_id', $student->id)->orWhere('email', $oldEmail))
                ->update([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'user_id' => $student->id,
                ]);

            $request->user()->school->trialRequests()->where('email', $oldEmail)->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $student->update([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
            ]);
        });

        return back()->with('success', 'La fiche élève a été mise à jour.');
    }

    public function destroy(Request $request, User $student, WaitlistService $waitlist): RedirectResponse
    {
        $this->authorizeStudent($request, $student);
        $school = $request->user()->school;
        $courseIds = [];

        DB::transaction(function () use ($school, $student, &$courseIds) {
            $enrollments = $school->enrollments()
                ->where(fn ($query) => $query->where('user_id', $student->id)->orWhere('email', $student->email))
                ->lockForUpdate()
                ->get();

            $courseIds = $enrollments->pluck('dance_course_id')->unique()->values()->all();
            foreach ($enrollments->whereIn('status', ['accepted', 'pending', 'invited'])->groupBy('dance_course_id') as $courseId => $occupied) {
                $course = DanceCourse::whereKey($courseId)->lockForUpdate()->first();
                if ($course) {
                    $course->update(['places' => min($course->capacity, $course->places + $occupied->count())]);
                }
            }

            $enrollments->each->delete();
            $school->trialRequests()->where('email', $student->email)->delete();
            $student->delete();
        });

        DanceCourse::whereIn('id', $courseIds)->get()->each(function (DanceCourse $course) use ($waitlist) {
            $waitlist->resequence($course);
            $waitlist->inviteNext($course->fresh());
        });

        return redirect()->route('admin.dashboard', ['section' => 'students'])->with('success', 'L’élève, ses inscriptions et ses factures ont été supprimés.');
    }

    public function show(Request $request, User $student): Response
    {
        $this->authorizeStudent($request, $student);

        $school = $request->user()->school;
        $enrollments = $school->enrollments()
            ->where(fn ($query) => $query->where('user_id', $student->id)->orWhere('email', $student->email))
            ->with(['course:id,title,style,level,teacher,location,day,time,start_date,end_date', 'invoice'])
            ->withSum('invoices', 'amount')
            ->latest()
            ->get();
        $enrollments->each(fn ($enrollment) => $enrollment->setAttribute(
            'amount',
            round((float) $enrollment->invoices_sum_amount, 2)
        ));
        $accepted = $enrollments->where('status', '!=', 'waitlist');
        $latest = $enrollments->first();
        $minorEnrollment = $enrollments->firstWhere('is_minor', true);

        return Inertia::render('Admin/Students/Show', [
            'student' => [
                ...$student->only('id', 'name', 'email', 'created_at', 'email_verified_at'),
                'phone' => $enrollments->firstWhere('phone', '!=', null)?->phone,
                'first_name' => $latest?->first_name,
                'last_name' => $latest?->last_name,
                'is_minor' => (bool) $minorEnrollment,
                'legal_guardian_first_name' => $minorEnrollment?->legal_guardian_first_name,
                'legal_guardian_last_name' => $minorEnrollment?->legal_guardian_last_name,
                'enrollments_count' => $enrollments->count(),
                'accepted_count' => $accepted->count(),
                'waitlist_count' => $enrollments->where('status', 'waitlist')->count(),
                'total_amount' => round((float) $enrollments->sum('invoices_sum_amount'), 2),
            ],
            'enrollments' => $enrollments,
            'trialRequests' => $school->trialRequests()->where('email', $student->email)->with('course:id,title,day,time,location')->latest()->get(),
        ]);
    }

    private function authorizeStudent(Request $request, User $student): void
    {
        abort_unless($student->school_id === $request->user()->school_id && ! $student->is_admin, 404);
    }
}
