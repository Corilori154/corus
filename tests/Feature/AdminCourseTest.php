<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\User;
use App\Models\School;
use App\Models\Enrollment;
use App\Models\DiscountRule;
use App\Models\SchoolLocation;
use App\Models\DanceDiscipline;
use App\Models\DanceLevel;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/connexion');
    }

    public function test_admin_can_create_a_published_course(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $location = SchoolLocation::create(['school_id' => $school->id, 'name' => 'Studio A']);
        $discipline = DanceDiscipline::create(['school_id' => $school->id, 'name' => 'Jazz']);
        $level = DanceLevel::create(['school_id' => $school->id, 'name' => 'Débutant']);
        $season = Season::create(['school_id' => $school->id, 'name' => 'Saison 2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-07-31']);

        $this->actingAs($admin)->post('/admin/cours', [
            'title' => 'Modern Jazz',
            'season_id' => $season->id,
            'dance_discipline_id' => $discipline->id,
            'dance_level_id' => $level->id,
            'school_location_id' => $location->id,
            'day' => 'Vendredi',
            'time' => '19:00 – 20:30',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'teacher' => 'Anna Martin',
            'description' => 'Un cours complet de modern jazz.',
            'capacity' => 15,
            'price' => 29,
            'session_price' => 116,
            'trial_is_free' => true,
            'trial_price' => 0,
            'image' => 'https://example.com/dance.jpg',
            'accent' => '#ef6f7f',
            'is_active' => true,
            'couple_mode' => false,
            'max_role_gap' => null,
            'balance_after_count' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('dance_courses', [
            'school_id' => $school->id,
            'title' => 'Modern Jazz',
            'season_id' => $season->id,
            'places' => 15,
            'is_active' => true,
            'trial_price' => 0,
        ]);
        $course = DanceCourse::where('title', 'Modern Jazz')->firstOrFail();
        $this->assertCount(4, $course->lessons);
    }

    public function test_non_admin_cannot_access_administration(): void
    {
        $school = School::factory()->create();
        $this->actingAs(User::factory()->create(['school_id' => $school->id]))->get('/admin')->assertForbidden();
    }

    public function test_admin_only_sees_courses_from_own_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        DanceCourse::factory()->for($school)->create(['title' => 'Notre cours']);
        DanceCourse::factory()->for($otherSchool)->create(['title' => 'Cours concurrent']);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn ($page) => $page
            ->has('courses', 1)
            ->where('courses.0.title', 'Notre cours')
        );
    }

    public function test_admin_can_update_and_delete_own_course(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $location = SchoolLocation::create(['school_id' => $school->id, 'name' => 'Grande salle']);
        $discipline = DanceDiscipline::create(['school_id' => $school->id, 'name' => 'Jazz']);
        $level = DanceLevel::create(['school_id' => $school->id, 'name' => 'Avancé']);
        $season = Season::create(['school_id' => $school->id, 'name' => 'Saison 2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-07-31']);

        $payload = [
            'title' => 'Cours modifié', 'season_id' => $season->id, 'dance_discipline_id' => $discipline->id, 'dance_level_id' => $level->id, 'school_location_id' => $location->id,
            'day' => 'Jeudi', 'time' => '20:00 – 21:00', 'start_date' => '2026-09-01',
            'end_date' => '2026-10-01', 'teacher' => 'Lina Martin', 'capacity' => 20,
            'description' => 'Nouvelle description du cours.',
            'price' => 30, 'session_price' => 150, 'image' => 'https://example.com/dance.jpg',
            'accent' => '#112233', 'is_active' => false, 'couple_mode' => true, 'max_role_gap' => 2, 'balance_after_count' => 6,
        ];

        $this->actingAs($admin)->put("/admin/cours/{$course->id}", $payload)->assertRedirect();
        $this->assertDatabaseHas('dance_courses', ['id' => $course->id, 'title' => 'Cours modifié', 'season_id' => $season->id, 'location' => 'Grande salle', 'is_active' => false]);
        $this->get("/ecole/{$school->slug}")->assertOk()->assertInertia(fn ($page) => $page->has('courses', 0));

        $this->actingAs($admin)->delete("/admin/cours/{$course->id}")->assertRedirect();
        $this->assertDatabaseMissing('dance_courses', ['id' => $course->id]);
    }

    public function test_admin_sees_only_own_school_enrollments(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $otherCourse = DanceCourse::factory()->for($otherSchool)->create();
        Enrollment::create(['school_id' => $school->id, 'dance_course_id' => $course->id, 'first_name' => 'Alice', 'last_name' => 'Martin', 'email' => 'alice@test.fr', 'start_date' => '2026-09-01', 'lessons_count' => 10, 'amount' => 250]);
        Enrollment::create(['school_id' => $otherSchool->id, 'dance_course_id' => $otherCourse->id, 'first_name' => 'Bob', 'last_name' => 'Durand', 'email' => 'bob@test.fr', 'start_date' => '2026-09-01', 'lessons_count' => 10, 'amount' => 250]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn ($page) => $page
            ->has('enrollments', 1)
            ->where('enrollments.0.email', 'alice@test.fr')
            ->has('students', 1)
            ->where('students.0.email', 'alice@test.fr')
            ->where('students.0.enrollments_count', 1)
        );
    }

    public function test_admin_can_create_and_delete_a_discount_tier(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);

        $this->actingAs($admin)->post('/admin/rabais', ['course_count' => 2, 'percentage' => 10])->assertRedirect();
        $rule = DiscountRule::where('school_id', $school->id)->firstOrFail();
        $this->assertEquals('10.00', $rule->percentage);

        $this->actingAs($admin)->delete("/admin/rabais/{$rule->id}")->assertRedirect();
        $this->assertDatabaseMissing('discount_rules', ['id' => $rule->id]);
    }
}
