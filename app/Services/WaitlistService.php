<?php

namespace App\Services;

use App\Models\DanceCourse;
use App\Notifications\WaitlistInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class WaitlistService
{
    public function inviteNext(DanceCourse $course): void
    {
        if (! $course->couple_mode) return;

        $invitation = DB::transaction(function () use ($course) {
            $lockedCourse = DanceCourse::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $expiredCount = $lockedCourse->enrollments()->where('status', 'invited')->where('waitlist_invitation_expires_at', '<=', now())->update([
                'status' => 'expired', 'waitlist_token_hash' => null,
                'waitlist_invited_at' => null, 'waitlist_invitation_expires_at' => null,
            ]);
            if ($expiredCount > 0) {
                $lockedCourse->update(['places' => min($lockedCourse->capacity, $lockedCourse->places + $expiredCount)]);
            }
            if ($lockedCourse->places <= 0 || $lockedCourse->enrollments()->where('status', 'invited')->exists()) return null;

            $active = $lockedCourse->enrollments()->whereNotIn('status', ['waitlist', 'expired'])->get();
            $leads = $active->where('dance_role', 'lead')->count();
            $follows = $active->where('dance_role', 'follow')->count();

            $candidate = $lockedCourse->enrollments()->where('status', 'waitlist')->oldest()->get()->first(function ($item) use ($leads, $follows, $lockedCourse) {
                $candidateLeads = $leads + ($item->dance_role === 'lead' ? 1 : 0);
                $candidateFollows = $follows + ($item->dance_role === 'follow' ? 1 : 0);
                return abs($candidateLeads - $candidateFollows) <= $lockedCourse->max_role_gap;
            });
            if (! $candidate) return null;

            $token = Str::random(64);
            $candidate->update([
                'status' => 'invited',
                'waitlist_token_hash' => hash('sha256', $token),
                'waitlist_invited_at' => now(),
                'waitlist_invitation_expires_at' => now()->addHours($lockedCourse->waitlist_invitation_hours),
            ]);
            $lockedCourse->decrement('places');

            return [$candidate->fresh(['school', 'course']), $token];
        });

        if ($invitation) Notification::route('mail', $invitation[0]->email)->notify(new WaitlistInvitation($invitation[0], $invitation[1]));
    }

    public function processExpiredInvitations(): int
    {
        $courses = DanceCourse::query()
            ->where('couple_mode', true)
            ->whereHas('enrollments', fn ($query) => $query->where('status', 'invited')->where('waitlist_invitation_expires_at', '<=', now()))
            ->get();

        $courses->each(fn (DanceCourse $course) => $this->inviteNext($course));

        return $courses->count();
    }
}
