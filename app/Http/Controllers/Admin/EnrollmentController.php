<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Services\WaitlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function destroy(Request $request, Enrollment $enrollment, WaitlistService $waitlist): RedirectResponse
    {
        abort_unless($enrollment->school_id === $request->user()->school_id, 404);

        $courseId = $enrollment->dance_course_id;
        $occupiedPlace = in_array($enrollment->status, ['accepted', 'pending', 'invited'], true);

        DB::transaction(function () use ($enrollment, $courseId, $occupiedPlace) {
            $course = DanceCourse::whereKey($courseId)->lockForUpdate()->first();

            // Invoice payments and invoices are removed by their cascading foreign keys.
            $enrollment->delete();

            if ($course && $occupiedPlace) {
                $course->update(['places' => min($course->capacity, $course->places + 1)]);
            }
        });

        if ($occupiedPlace && ($course = DanceCourse::find($courseId))) {
            $waitlist->inviteNext($course);
        }

        return back()->with('success', 'L’inscription et ses factures ont été annulées.');
    }
}
