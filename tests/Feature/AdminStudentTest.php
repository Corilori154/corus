<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_students_complete_record(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $student = User::factory()->create(['school_id' => $school->id, 'name' => 'Camille Dupont', 'email' => 'camille@example.ch', 'is_admin' => false]);
        $course = DanceCourse::factory()->for($school)->create(['title' => 'Salsa débutant']);
        Enrollment::create([
            'school_id' => $school->id, 'user_id' => $student->id, 'dance_course_id' => $course->id,
            'first_name' => 'Camille', 'last_name' => 'Dupont', 'email' => $student->email,
            'phone' => '+41 79 123 45 67', 'start_date' => '2026-09-01', 'lessons_count' => 10,
            'base_amount' => 300, 'discount_amount' => 30, 'discount_percentage' => 10,
            'amount' => 270, 'installment_count' => 1, 'installment_amount' => 270, 'status' => 'pending',
        ]);

        $this->actingAs($admin)->get("/admin/eleves/{$student->id}")
            ->assertOk()->assertInertia(fn ($page) => $page
                ->component('Admin/Students/Show')
                ->where('student.name', 'Camille Dupont')
                ->where('student.phone', '+41 79 123 45 67')
                ->where('student.total_amount', 270)
                ->has('enrollments', 1)
                ->where('enrollments.0.course.title', 'Salsa débutant')
            );
    }

    public function test_admin_cannot_view_another_schools_student(): void
    {
        $adminSchool = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $adminSchool->id, 'is_admin' => true]);
        $otherStudent = User::factory()->create(['school_id' => School::factory()->create()->id, 'is_admin' => false]);

        $this->actingAs($admin)->get("/admin/eleves/{$otherStudent->id}")->assertNotFound();
    }
}
