<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_log_in_and_view_their_school_portal(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->for($school)->create([
            'is_admin' => false,
            'email' => 'eleve@example.ch',
            'password' => 'password-test',
        ]);
        $course = DanceCourse::factory()->for($school)->create();
        $enrollment = Enrollment::create([
            'school_id' => $school->id,
            'user_id' => $student->id,
            'dance_course_id' => $course->id,
            'first_name' => 'Camille',
            'last_name' => 'Martin',
            'email' => $student->email,
            'phone' => '+41790000000',
            'start_date' => '2026-09-01',
            'lessons_count' => 10,
            'base_amount' => 200,
            'amount' => 200,
            'installment_amount' => 200,
            'installment_count' => 1,
            'status' => 'accepted',
        ]);
        Invoice::create([
            'school_id' => $school->id,
            'enrollment_id' => $enrollment->id,
            'number' => 'TEST-001',
            'status' => 'open',
            'currency' => 'CHF',
            'amount' => 200,
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
        ]);

        $this->post(route('students.login.store', $school), [
            'email' => $student->email,
            'password' => 'password-test',
        ])->assertRedirect(route('student.dashboard', $school));

        $this->get(route('student.dashboard', $school))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('school.id', $school->id)
                ->has('enrollments', 1)
                ->where('enrollments.0.course.id', $course->id)
                ->has('enrollments.0.invoices', 1));
    }

    public function test_student_cannot_access_another_schools_portal(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $student = User::factory()->for($school)->create(['is_admin' => false]);

        $this->actingAs($student)
            ->get(route('student.dashboard', $otherSchool))
            ->assertForbidden();
    }

    public function test_administrator_credentials_are_rejected_by_student_login(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->for($school)->create([
            'is_admin' => true,
            'password' => 'password-test',
        ]);

        $this->post(route('students.login.store', $school), [
            'email' => $admin->email,
            'password' => 'password-test',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
