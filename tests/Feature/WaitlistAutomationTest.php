<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\User;
use App\Notifications\WaitlistInvitation;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WaitlistAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_eligible_waitlisted_dancer_is_invited_and_can_accept(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create([
            'couple_mode' => true, 'max_role_gap' => 2, 'balance_after_count' => 3, 'places' => 5,
        ]);
        foreach (['lead', 'lead', 'follow', 'follow', 'follow'] as $index => $role) {
            $this->enrollment($school, $course, "active{$index}@example.ch", $role, 'pending');
        }
        $candidateUser = User::factory()->create(['school_id' => $school->id, 'email' => 'candidate@example.ch']);
        $candidate = $this->enrollment($school, $course, $candidateUser->email, 'follow', 'waitlist', $candidateUser->id);

        app(WaitlistService::class)->inviteNext($course);

        $candidate->refresh();
        $this->assertSame('invited', $candidate->status);
        $this->assertNotNull($candidate->waitlist_invited_at);
        $token = null;
        Notification::assertSentOnDemand(WaitlistInvitation::class, function (WaitlistInvitation $notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        $url = URL::temporarySignedRoute('waitlist.accept', now()->addHour(), ['enrollment' => $candidate->id, 'token' => $token]);
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Waitlist/Confirmed')
            ->where('course.title', $course->title)
            ->where('enrollment.email', $candidate->email)
        );

        $candidate->refresh();
        $this->assertSame('accepted', $candidate->status);
        $this->assertNull($candidate->waitlist_token_hash);
        $this->assertDatabaseHas('invoices', ['enrollment_id' => $candidate->id, 'status' => 'open']);
        $this->assertSame(4, $course->fresh()->places);
    }

    public function test_expired_invitation_is_passed_to_the_next_person(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create([
            'couple_mode' => true, 'max_role_gap' => 2, 'places' => 5, 'waitlist_invitation_hours' => 1,
        ]);
        foreach (['lead', 'lead', 'follow', 'follow', 'follow'] as $index => $role) {
            $this->enrollment($school, $course, "balance{$index}@example.ch", $role, 'pending');
        }
        $first = $this->enrollment($school, $course, 'first@example.ch', 'follow', 'waitlist');
        $second = $this->enrollment($school, $course, 'second@example.ch', 'follow', 'waitlist');

        app(WaitlistService::class)->inviteNext($course);
        $this->assertSame('invited', $first->fresh()->status);

        $this->travel(61)->minutes();
        $this->artisan('waitlist:process')->assertSuccessful();

        $this->assertSame('expired', $first->fresh()->status);
        $this->assertSame('invited', $second->fresh()->status);
        Notification::assertSentOnDemandTimes(WaitlistInvitation::class, 2);
    }

    private function enrollment(School $school, DanceCourse $course, string $email, string $role, string $status, ?int $userId = null): Enrollment
    {
        return Enrollment::create([
            'school_id' => $school->id, 'user_id' => $userId, 'dance_course_id' => $course->id,
            'first_name' => 'Test', 'last_name' => ucfirst($role), 'email' => $email, 'phone' => '+41790000000',
            'dance_role' => $role, 'start_date' => '2026-09-01', 'lessons_count' => 10,
            'base_amount' => 100, 'amount' => 100, 'installment_amount' => 100, 'installment_count' => 1,
            'status' => $status,
        ]);
    }
}
