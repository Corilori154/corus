<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\School;
use App\Notifications\InvoiceCreated;
use App\Services\InvoiceService;
use App\Services\RegistrationFeeService;
use App\Services\WaitlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function reposition(Request $request, Enrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->school_id === $request->user()->school_id, 404);
        abort_unless(in_array($enrollment->status, ['waitlist', 'invited', 'expired'], true), 422);

        $data = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($enrollment, $data) {
            $items = Enrollment::where('dance_course_id', $enrollment->dance_course_id)
                ->whereIn('status', ['waitlist', 'invited', 'expired'])
                ->lockForUpdate()
                ->orderByRaw('waitlist_position IS NULL')
                ->orderBy('waitlist_position')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $target = $items->firstWhere('id', $enrollment->id);
            $ordered = $items->reject(fn (Enrollment $item) => $item->id === $enrollment->id)->values();
            $ordered->splice(min($data['position'] - 1, $ordered->count()), 0, [$target]);

            $ordered->each(fn (Enrollment $item, int $index) => $item->update([
                'waitlist_position' => $index + 1,
            ]));
        });

        return back()->with('success', 'L’ordre de la liste d’attente a été mis à jour.');
    }

    public function forceAccept(
        Request $request,
        Enrollment $enrollment,
        InvoiceService $invoices,
        RegistrationFeeService $fees,
        WaitlistService $waitlist,
    ): RedirectResponse {
        abort_unless($enrollment->school_id === $request->user()->school_id, 404);
        abort_unless(in_array($enrollment->status, ['waitlist', 'invited', 'expired'], true), 422);

        [$accepted, $course, $invoice] = DB::transaction(function () use ($enrollment, $invoices, $fees) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['waitlist', 'invited', 'expired'], true), 422);

            $course = DanceCourse::whereKey($locked->dance_course_id)->lockForUpdate()->firstOrFail();
            $school = School::whereKey($locked->school_id)->lockForUpdate()->firstOrFail();
            $placeAlreadyReserved = $locked->status === 'invited';
            $fee = $fees->amountFor($school, $course, $locked->email, $locked->id);
            $amount = round((float) $locked->amount + $fee, 2);

            $locked->update([
                'status' => 'accepted',
                'waitlist_position' => null,
                'amount' => $amount,
                'installment_amount' => round($amount / max(1, $locked->installment_count), 2),
                'registration_fee_name' => $fee > 0 ? $school->registration_fee_name : null,
                'registration_fee_amount' => $fee,
                'waitlist_token_hash' => null,
                'waitlist_invited_at' => null,
                'waitlist_invitation_expires_at' => null,
            ]);

            if (! $placeAlreadyReserved && $course->places > 0) {
                $course->decrement('places');
            }

            return [$locked->fresh(['user', 'course']), $course->fresh(), $invoices->createFor($locked->fresh())];
        });

        $accepted->user?->notify(new InvoiceCreated($invoice));
        $waitlist->resequence($course);
        $waitlist->inviteNext($course->fresh());

        return back()->with('success', "{$accepted->first_name} {$accepted->last_name} a été accepté·e dans le cours et sa facture a été créée.");
    }

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

        if ($course = DanceCourse::find($courseId)) {
            $waitlist->resequence($course);
        }

        return back()->with('success', 'L’inscription et ses factures ont été annulées.');
    }
}
