<?php

namespace App\Http\Controllers;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Notifications\InvoiceCreated;
use App\Services\InvoiceService;
use App\Services\WaitlistService;
use App\Services\RegistrationFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    public function accept(Request $request, Enrollment $enrollment, InvoiceService $invoices, WaitlistService $waitlist, RegistrationFeeService $fees): Response
    {
        $token = (string) $request->query('token');
        $result = DB::transaction(function () use ($enrollment, $token, $invoices, $fees) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'invited' || ! $locked->waitlist_token_hash || ! hash_equals($locked->waitlist_token_hash, hash('sha256', $token))) {
                throw ValidationException::withMessages(['invitation' => 'Cette invitation a déjà été utilisée ou n’est pas valide.']);
            }
            if (! $locked->waitlist_invitation_expires_at || $locked->waitlist_invitation_expires_at->isPast()) {
                $locked->update(['status' => 'waitlist', 'waitlist_token_hash' => null, 'waitlist_invited_at' => null, 'waitlist_invitation_expires_at' => null]);
                throw ValidationException::withMessages(['invitation' => 'Cette invitation a expiré. Contactez l’école pour obtenir une nouvelle invitation.']);
            }

            $course = DanceCourse::whereKey($locked->dance_course_id)->lockForUpdate()->firstOrFail();
            $school = \App\Models\School::whereKey($locked->school_id)->lockForUpdate()->firstOrFail();
            $active = $course->enrollments()->whereNotIn('status', ['waitlist', 'expired'])->get();
            if (abs($active->where('dance_role', 'lead')->count() - $active->where('dance_role', 'follow')->count()) > $course->max_role_gap) {
                throw ValidationException::withMessages(['invitation' => 'La place n’est momentanément plus disponible. Votre priorité sur la liste d’attente est conservée.']);
            }

            $fee = $fees->amountFor($school, $course, $locked->email, $locked->id);
            $amount = round((float) $locked->amount + $fee, 2);
            $locked->update(['status' => 'accepted', 'waitlist_position' => null, 'amount' => $amount, 'installment_amount' => round($amount / max(1, $locked->installment_count), 2), 'registration_fee_name' => $fee > 0 ? $school->registration_fee_name : null, 'registration_fee_amount' => $fee, 'waitlist_token_hash' => null, 'waitlist_invited_at' => null, 'waitlist_invitation_expires_at' => null]);
            $invoice = $invoices->createFor($locked->fresh());

            return [$locked->fresh(['user', 'school', 'course']), $course->fresh(), $invoice];
        });

        $result[0]->user?->notify(new InvoiceCreated($result[2]));
        $waitlist->inviteNext($result[1]);

        return Inertia::render('Waitlist/Confirmed', [
            'school' => $result[0]->school->only('name', 'slug', 'accent'),
            'course' => $result[0]->course->only('title', 'day', 'time', 'location'),
            'enrollment' => $result[0]->only('first_name', 'last_name', 'email', 'dance_role', 'start_date'),
            'invoice' => $result[2]->only('number', 'amount'),
        ]);
    }
}
