<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_an_enrollment_and_its_invoice(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create(['capacity' => 12, 'places' => 11]);
        $enrollment = Enrollment::create([
            'school_id' => $school->id, 'dance_course_id' => $course->id,
            'first_name' => 'Lina', 'last_name' => 'Test', 'email' => 'lina@example.ch',
            'start_date' => '2026-09-01', 'lessons_count' => 10,
            'base_amount' => 200, 'amount' => 200, 'status' => 'accepted',
        ]);
        $invoice = Invoice::create([
            'school_id' => $school->id, 'enrollment_id' => $enrollment->id,
            'number' => 'INV-TEST', 'amount' => 200,
            'issued_at' => now(), 'due_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)->delete("/admin/inscriptions/{$enrollment->id}")
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertSame(12, $course->fresh()->places);
    }

    public function test_admin_cannot_cancel_another_schools_enrollment(): void
    {
        $admin = User::factory()->create(['school_id' => School::factory()->create()->id, 'is_admin' => true]);
        $otherSchool = School::factory()->create();
        $course = DanceCourse::factory()->for($otherSchool)->create();
        $enrollment = Enrollment::create([
            'school_id' => $otherSchool->id, 'dance_course_id' => $course->id,
            'first_name' => 'Lina', 'last_name' => 'Test', 'email' => 'other@example.ch',
            'start_date' => '2026-09-01', 'lessons_count' => 10,
            'base_amount' => 200, 'amount' => 200, 'status' => 'accepted',
        ]);

        $this->actingAs($admin)->delete("/admin/inscriptions/{$enrollment->id}")->assertNotFound();
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
    }

    public function test_admin_can_change_waitlist_order_for_a_course(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $first = $this->waitlisted($school, $course, 'first@example.ch', 1);
        $second = $this->waitlisted($school, $course, 'second@example.ch', 2);
        $third = $this->waitlisted($school, $course, 'third@example.ch', 3);

        $this->actingAs($admin)
            ->patch("/admin/inscriptions/{$third->id}/position-liste-attente", ['position' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, $third->fresh()->waitlist_position);
        $this->assertSame(2, $first->fresh()->waitlist_position);
        $this->assertSame(3, $second->fresh()->waitlist_position);
    }

    public function test_admin_can_force_accept_a_waitlisted_person_when_course_is_full(): void
    {
        $school = School::factory()->create(['registration_fee_enabled' => false]);
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create(['capacity' => 1, 'places' => 0]);
        $enrollment = $this->waitlisted($school, $course, 'forced@example.ch', 1);

        $this->actingAs($admin)
            ->post("/admin/inscriptions/{$enrollment->id}/forcer-acceptation")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => 'accepted',
            'waitlist_position' => null,
        ]);
        $this->assertDatabaseHas('invoices', ['enrollment_id' => $enrollment->id]);
        $this->assertSame(0, $course->fresh()->places);
    }

    public function test_admin_can_remove_a_person_from_the_waitlist_and_positions_are_resequenced(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $first = $this->waitlisted($school, $course, 'first-remove@example.ch', 1);
        $second = $this->waitlisted($school, $course, 'second-remove@example.ch', 2);

        $this->actingAs($admin)
            ->delete("/admin/inscriptions/{$first->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('enrollments', ['id' => $first->id]);
        $this->assertSame(1, $second->fresh()->waitlist_position);
    }

    private function waitlisted(School $school, DanceCourse $course, string $email, int $position): Enrollment
    {
        return Enrollment::create([
            'school_id' => $school->id,
            'dance_course_id' => $course->id,
            'first_name' => 'Élève',
            'last_name' => (string) $position,
            'email' => $email,
            'phone' => '+41790000000',
            'start_date' => '2026-09-01',
            'lessons_count' => 10,
            'base_amount' => 200,
            'amount' => 200,
            'installment_amount' => 200,
            'installment_count' => 1,
            'status' => 'waitlist',
            'waitlist_position' => $position,
        ]);
    }
}
