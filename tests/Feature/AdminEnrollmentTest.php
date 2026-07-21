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
}
