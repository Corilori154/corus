<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\School;
use App\Models\User;
use App\Models\DiscountRule;
use App\Models\Season;
use App\Models\Enrollment;
use App\Models\PricingCategory;
use App\Models\PaymentPlan;
use App\Notifications\StudentAccountCreated;
use App\Notifications\InvoiceCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourseCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_catalog_is_displayed(): void
    {
        $school = School::factory()->create();
        DanceCourse::factory()->count(6)->for($school)->create();

        $this->get("/ecole/{$school->slug}")->assertOk()->assertInertia(fn ($page) => $page
            ->component('Courses/Index')
            ->has('courses', 6)
        );
    }

    public function test_course_detail_page_is_displayed_only_for_its_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create();

        $this->get("/ecole/{$school->slug}/cours/{$course->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Courses/Show')->where('course.id', $course->id));
        $this->get("/ecole/{$otherSchool->slug}/cours/{$course->id}")->assertNotFound();
    }

    public function test_a_guest_can_submit_an_enrollment_request(): void
    {
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create();

        $this->post("/ecole/{$school->slug}/inscriptions", [
            'course_id' => $course->id,
            'first_name' => 'Camille',
            'last_name' => 'Dupont',
            'email' => 'camille@example.com',
            'phone' => '+41 79 123 45 67',
            'start_date' => '2026-09-01',
        ])->assertRedirectContains('/facture/')->assertSessionHas('success');
    }

    public function test_enrollment_requires_valid_contact_details(): void
    {
        $school = School::factory()->create();

        $this->post("/ecole/{$school->slug}/inscriptions", [
            'course_id' => 99,
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalide',
            'phone' => '',
            'start_date' => '',
        ])->assertSessionHasErrors(['course_id', 'first_name', 'last_name', 'email', 'phone', 'start_date']);
    }

    public function test_session_price_is_prorated_from_the_chosen_start_date(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create([
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'session_price' => 400,
        ]);
        $course->lessons()->createMany([
            ['lesson_date' => '2026-09-01'],
            ['lesson_date' => '2026-09-08'],
            ['lesson_date' => '2026-09-15'],
            ['lesson_date' => '2026-09-22'],
        ]);

        $this->post("/ecole/{$school->slug}/inscriptions", [
            'course_id' => $course->id,
            'first_name' => 'Camille',
            'last_name' => 'Dupont',
            'email' => 'camille@example.com',
            'phone' => '+41 79 123 45 67',
            'start_date' => '2026-09-15',
        ])->assertRedirect()->assertSessionHas('success', fn ($message) => str_contains($message, '200,00 CHF') && str_contains($message, '2 leçons'));

        $this->assertDatabaseHas('enrollments', [
            'school_id' => $school->id,
            'dance_course_id' => $course->id,
            'email' => 'camille@example.com',
            'lessons_count' => 2,
            'amount' => 200,
        ]);
        $enrollment = Enrollment::where('email', 'camille@example.com')->firstOrFail();
        $this->assertDatabaseHas('invoices', [
            'school_id' => $school->id,
            'enrollment_id' => $enrollment->id,
            'currency' => 'CHF',
            'amount' => 200,
            'status' => 'open',
        ]);
        $customer = User::where('email', 'camille@example.com')->firstOrFail();
        $this->assertFalse($customer->is_admin);
        $this->assertDatabaseHas('enrollments', ['user_id' => $customer->id, 'email' => 'camille@example.com']);
        Notification::assertSentTo($customer, StudentAccountCreated::class);
        Notification::assertSentTo($customer, InvoiceCreated::class);
    }

    public function test_second_course_invoice_deducts_discount_on_both_courses(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        DiscountRule::create(['school_id' => $school->id, 'course_count' => 2, 'percentage' => 10]);
        $firstCourse = DanceCourse::factory()->for($school)->create(['session_price' => 100]);
        $secondCourse = DanceCourse::factory()->for($school)->create(['session_price' => 200]);
        foreach ([$firstCourse, $secondCourse] as $course) {
            $course->lessons()->create(['lesson_date' => '2026-09-01']);
        }
        $customer = ['first_name' => 'Camille', 'last_name' => 'Dupont', 'email' => 'multi@example.com', 'phone' => '+41 79 123 45 67', 'start_date' => '2026-09-01'];

        $this->post("/ecole/{$school->slug}/inscriptions", [...$customer, 'course_id' => $firstCourse->id])->assertRedirect();
        $this->postJson("/ecole/{$school->slug}/devis", [
            'email' => 'multi@example.com',
            'course_id' => $secondCourse->id,
            'start_date' => '2026-09-01',
        ])->assertOk()->assertJson([
            'base_amount' => 200,
            'amount' => 170,
            'discount_amount' => 30,
            'discount_percentage' => 10,
            'course_count' => 2,
        ]);
        $this->post("/ecole/{$school->slug}/inscriptions", [...$customer, 'course_id' => $secondCourse->id])->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'dance_course_id' => $firstCourse->id, 'base_amount' => 100, 'discount_amount' => 0, 'amount' => 100,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'dance_course_id' => $secondCourse->id, 'base_amount' => 200, 'discount_percentage' => 10,
            'discount_amount' => 30, 'amount' => 170,
        ]);
    }

    public function test_multi_course_discount_is_limited_to_the_same_season(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        DiscountRule::create(['school_id' => $school->id, 'course_count' => 2, 'percentage' => 10]);
        $autumn = Season::create(['school_id' => $school->id, 'name' => 'Automne', 'start_date' => '2026-08-01', 'end_date' => '2026-12-31']);
        $spring = Season::create(['school_id' => $school->id, 'name' => 'Printemps', 'start_date' => '2027-01-01', 'end_date' => '2027-06-30']);
        $first = DanceCourse::factory()->for($school)->create(['season_id' => $autumn->id, 'session_price' => 100]);
        $second = DanceCourse::factory()->for($school)->create(['season_id' => $spring->id, 'session_price' => 200, 'start_date' => '2027-02-01', 'end_date' => '2027-06-01']);
        $first->lessons()->create(['lesson_date' => '2026-09-01']);
        $second->lessons()->create(['lesson_date' => '2027-02-01']);
        $customer = ['first_name' => 'Camille', 'last_name' => 'Dupont', 'email' => 'season@example.com', 'phone' => '+41 79 123 45 67'];

        $this->post("/ecole/{$school->slug}/inscriptions", [...$customer, 'course_id' => $first->id, 'start_date' => '2026-09-01'])->assertRedirect();
        $this->postJson("/ecole/{$school->slug}/devis", ['email' => $customer['email'], 'course_id' => $second->id, 'start_date' => '2027-02-01'])
            ->assertOk()->assertJson(['course_count' => 1, 'discount_amount' => 0, 'discount_percentage' => 0, 'amount' => 200]);
    }

    public function test_follow_is_waitlisted_when_couple_gap_would_be_exceeded(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create([
            'couple_mode' => true,
            'max_role_gap' => 2,
            'places' => 10,
        ]);
        $course->lessons()->create(['lesson_date' => '2026-09-01']);

        foreach ([['lead', 1], ['lead', 2], ['follow', 1], ['follow', 2], ['follow', 3], ['follow', 4]] as [$role, $index]) {
            Enrollment::create([
                'school_id' => $school->id, 'dance_course_id' => $course->id,
                'first_name' => ucfirst($role), 'last_name' => (string) $index,
                'email' => "{$role}{$index}@example.com", 'dance_role' => $role,
                'start_date' => '2026-09-01', 'lessons_count' => 1,
                'base_amount' => 100, 'amount' => 100, 'status' => 'accepted',
            ]);
        }

        $this->post("/ecole/{$school->slug}/inscriptions", [
            'course_id' => $course->id, 'first_name' => 'Nouvelle', 'last_name' => 'Follow',
            'email' => 'new-follow@example.com', 'phone' => '+41 79 123 45 67', 'dance_role' => 'follow', 'start_date' => '2026-09-01',
        ])->assertRedirect()->assertSessionHas('waitlist', fn ($message) => str_contains($message, 'liste d’attente'));

        $this->assertDatabaseHas('enrollments', [
            'dance_course_id' => $course->id, 'email' => 'new-follow@example.com',
            'dance_role' => 'follow', 'status' => 'waitlist',
        ]);
        $this->assertEquals(10, $course->fresh()->places);
    }

    public function test_balance_rule_starts_only_after_configured_number_of_enrollments(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create([
            'couple_mode' => true, 'max_role_gap' => 2, 'balance_after_count' => 6, 'places' => 10,
        ]);
        $course->lessons()->create(['lesson_date' => '2026-09-01']);

        for ($index = 1; $index <= 5; $index++) {
            Enrollment::create([
                'school_id' => $school->id, 'dance_course_id' => $course->id,
                'first_name' => 'Follow', 'last_name' => (string) $index,
                'email' => "threshold{$index}@example.com", 'dance_role' => 'follow',
                'start_date' => '2026-09-01', 'lessons_count' => 1,
                'base_amount' => 100, 'amount' => 100, 'status' => 'accepted',
            ]);
        }

        $payload = ['course_id' => $course->id, 'last_name' => 'Test', 'phone' => '+41 79 123 45 67', 'dance_role' => 'follow', 'start_date' => '2026-09-01'];
        $this->post("/ecole/{$school->slug}/inscriptions", [...$payload, 'first_name' => 'Sixième', 'email' => 'six@example.com'])->assertRedirect();
        $this->assertDatabaseHas('enrollments', ['email' => 'six@example.com', 'status' => 'accepted']);

        $this->post("/ecole/{$school->slug}/inscriptions", [...$payload, 'first_name' => 'Septième', 'email' => 'seven@example.com'])->assertRedirect();
        $this->assertDatabaseHas('enrollments', ['email' => 'seven@example.com', 'status' => 'waitlist']);
    }

    public function test_course_pricing_category_price_is_applied_to_quote_and_enrollment(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $category = PricingCategory::create(['school_id' => $school->id, 'name' => 'Enfant']);
        $course = DanceCourse::factory()->for($school)->create(['session_price' => 100]);
        $course->pricingCategories()->attach($category, ['price' => 80]);
        $course->lessons()->create(['lesson_date' => '2026-09-01']);

        $this->postJson("/ecole/{$school->slug}/devis", [
            'email' => 'enfant@example.com', 'course_id' => $course->id,
            'start_date' => '2026-09-01', 'pricing_category_id' => $category->id,
        ])->assertOk()->assertJson(['list_amount' => 100, 'base_amount' => 80, 'category_discount_amount' => 0, 'amount' => 80]);

        $this->post("/ecole/{$school->slug}/inscriptions", [
            'course_id' => $course->id, 'first_name' => 'Petit', 'last_name' => 'Danseur',
            'email' => 'enfant@example.com', 'phone' => '+41 79 123 45 67',
            'start_date' => '2026-09-01', 'pricing_category_id' => $category->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'email' => 'enfant@example.com', 'pricing_category_name' => 'Enfant',
            'category_discount_amount' => 0, 'amount' => 80,
        ]);
    }

    public function test_payment_plan_adds_fee_and_calculates_installments(): void
    {
        Notification::fake();
        $school = School::factory()->create();
        $plan = PaymentPlan::create([
            'school_id' => $school->id, 'name' => 'Deux fois avec frais', 'installment_count' => 2,
            'schedule_mode' => 'monthly_end',
            'adjustment_direction' => 'fee', 'adjustment_mode' => 'percentage', 'adjustment_value' => 10, 'is_active' => true,
        ]);
        $course = DanceCourse::factory()->for($school)->create(['session_price' => 100]);
        $course->lessons()->create(['lesson_date' => '2026-09-01']);

        $this->postJson("/ecole/{$school->slug}/devis", [
            'email' => 'plan@example.com', 'course_id' => $course->id,
            'start_date' => '2026-09-01', 'payment_plan_id' => $plan->id,
        ])->assertOk()->assertJson([
            'amount' => 110, 'payment_adjustment_amount' => 10,
            'installment_count' => 2, 'installment_amount' => 55,
        ]);

        $this->post("/ecole/{$school->slug}/inscriptions", [
            'course_id' => $course->id, 'first_name' => 'Plan', 'last_name' => 'Test',
            'email' => 'plan@example.com', 'phone' => '+41 79 123 45 67',
            'start_date' => '2026-09-01', 'payment_plan_id' => $plan->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'email' => 'plan@example.com', 'payment_plan_name' => 'Deux fois avec frais',
            'amount' => 110, 'installment_count' => 2, 'installment_amount' => 55,
        ]);
        $enrollment = Enrollment::where('email', 'plan@example.com')->firstOrFail();
        $invoices = $enrollment->invoices()->get();
        $this->assertCount(2, $invoices);
        $this->assertSame(55.0, (float) $invoices[0]->amount);
        $this->assertSame(55.0, (float) $invoices[1]->amount);
        $this->assertSame(today()->toDateString(), $invoices[0]->due_at->toDateString());
        $this->assertSame(today()->addMonthNoOverflow()->endOfMonth()->toDateString(), $invoices[1]->due_at->toDateString());

        $customer = User::where('email', 'plan@example.com')->firstOrFail();
        Notification::assertSentTo($customer, InvoiceCreated::class, function (InvoiceCreated $notification) use ($customer, $invoices) {
            $mail = $notification->toMail($customer);
            $content = implode("\n", $mail->introLines);

            return str_contains($mail->subject, 'Vos factures')
                && str_contains($content, $invoices[0]->number)
                && str_contains($content, $invoices[1]->number)
                && str_contains($content, 'Facture 1/2')
                && str_contains($content, 'Facture 2/2');
        });
    }

    public function test_guest_can_request_a_trial_lesson(): void
    {
        $school = School::factory()->create();
        $course = DanceCourse::factory()->for($school)->create();
        $course->lessons()->create(['lesson_date' => '2026-09-08']);

        $this->post("/ecole/{$school->slug}/cours-essai", [
            'course_id' => $course->id, 'first_name' => 'Emma', 'last_name' => 'Test',
            'email' => 'emma@example.com', 'phone' => '+41 79 123 45 67',
            'preferred_date' => '2026-09-08', 'message' => 'Je débute.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('trial_requests', [
            'school_id' => $school->id, 'dance_course_id' => $course->id,
            'email' => 'emma@example.com', 'preferred_date' => '2026-09-08', 'status' => 'pending',
        ]);
    }
}
