<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SwissInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_invoice_manually_for_an_enrollment(): void
    {
        $school = School::factory()->create(['invoice_prefix' => 'MAN']);
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $enrollment = Enrollment::create([
            'school_id' => $school->id, 'dance_course_id' => $course->id,
            'first_name' => 'Lina', 'last_name' => 'Meier', 'email' => 'lina@example.ch',
            'start_date' => '2026-09-01', 'lessons_count' => 1, 'base_amount' => 120,
            'amount' => 120, 'installment_amount' => 120, 'installment_count' => 1, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->post('/admin/factures', [
            'enrollment_id' => $enrollment->id,
            'amount' => 89.90,
            'issued_at' => '2026-08-06',
            'due_at' => '2026-08-31',
        ]);

        $invoice = $school->invoices()->firstOrFail();
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame('MAN-2026-'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT), $invoice->number);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id, 'enrollment_id' => $enrollment->id, 'amount' => 89.90,
            'issued_at' => '2026-08-06', 'due_at' => '2026-08-31', 'status' => 'open',
        ]);
    }

    public function test_admin_cannot_create_an_invoice_for_another_schools_enrollment(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $otherSchool = School::factory()->create();
        $course = DanceCourse::factory()->for($otherSchool)->create();
        $enrollment = Enrollment::create([
            'school_id' => $otherSchool->id, 'dance_course_id' => $course->id,
            'first_name' => 'Noa', 'last_name' => 'Rossi', 'email' => 'noa@example.ch',
            'start_date' => '2026-09-01', 'lessons_count' => 1, 'base_amount' => 100,
            'amount' => 100, 'installment_amount' => 100, 'installment_count' => 1, 'status' => 'confirmed',
        ]);

        $this->actingAs($admin)->post('/admin/factures', [
            'enrollment_id' => $enrollment->id, 'amount' => 50,
            'issued_at' => '2026-08-06', 'due_at' => '2026-08-31',
        ])->assertSessionHasErrors('enrollment_id');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_admin_can_manage_and_open_the_swiss_qr_invoice_document(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $enrollment = Enrollment::create([
            'school_id' => $school->id, 'user_id' => $admin->id, 'dance_course_id' => $course->id,
            'first_name' => 'Pia', 'last_name' => 'Rutschmann', 'email' => 'pia@example.ch', 'phone' => '+41 79 111 22 33',
            'start_date' => '2026-09-01', 'lessons_count' => 10, 'base_amount' => 250.25,
            'category_discount_amount' => 0, 'discount_amount' => 0, 'discount_percentage' => 0,
            'payment_adjustment_amount' => 0, 'amount' => 250.25, 'installment_amount' => 250.25,
            'installment_count' => 1, 'status' => 'pending',
        ]);
        $invoice = app(InvoiceService::class)->createFor($enrollment);

        $this->actingAs($admin)->put('/admin/facturation', [
            'billing_name' => 'École Corus', 'billing_street' => 'Rue du Lac', 'billing_house_number' => '12',
            'billing_postal_code' => '2501', 'billing_city' => 'Bienne', 'billing_country' => 'CH',
            'billing_iban' => 'CH9300762011623852957', 'invoice_prefix' => 'FAC', 'invoice_due_days' => 30,
        ])->assertRedirect();

        $this->actingAs($admin)->get("/admin/factures/{$invoice->id}")
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Invoices/Show')
                ->where('invoice.number', $invoice->number)
                ->where('invoice.balance', 250.25)
                ->where('documentUrl', route('admin.invoices.document', $invoice))
            );
        $this->actingAs($admin)->get("/admin/factures/{$invoice->id}/document")
            ->assertOk()->assertSee($invoice->number)->assertSee('250,25 CHF')->assertSee('qr-bill-swiss-qr-image');

        $this->actingAs($admin)->post("/admin/factures/{$invoice->id}/paiements", [
            'amount' => 100, 'paid_on' => '2026-07-20', 'method' => 'bank_transfer', 'note' => 'Premier acompte',
        ])->assertRedirect();
        $invoice->refresh()->load('payments');
        $this->assertSame(100.0, $invoice->paid_amount);
        $this->assertSame(150.25, $invoice->balance);
        $this->assertSame('partial', $invoice->payment_status);
        $this->actingAs($admin)->get("/admin/factures/{$invoice->id}")
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('invoice.balance', 150.25)
                ->where('invoice.payment_status', 'partial')
            );

        $this->actingAs($admin)->post("/admin/factures/{$invoice->id}/paiements", [
            'amount' => 151, 'paid_on' => '2026-07-20', 'method' => 'cash',
        ])->assertSessionHasErrors('amount');
    }

    public function test_an_admin_cannot_view_another_schools_invoice(): void
    {
        $school = School::factory()->create();
        $otherAdmin = User::factory()->create(['school_id' => School::factory()->create()->id, 'is_admin' => true]);
        $course = DanceCourse::factory()->for($school)->create();
        $enrollment = Enrollment::create([
            'school_id' => $school->id, 'dance_course_id' => $course->id, 'first_name' => 'A', 'last_name' => 'B',
            'email' => 'a@example.ch', 'phone' => '+41790000000', 'start_date' => '2026-09-01', 'lessons_count' => 1,
            'base_amount' => 10, 'amount' => 10, 'installment_amount' => 10, 'installment_count' => 1, 'status' => 'pending',
        ]);
        $invoice = app(InvoiceService::class)->createFor($enrollment);

        $this->actingAs($otherAdmin)->get("/admin/factures/{$invoice->id}")->assertNotFound();
        $this->actingAs($otherAdmin)->get("/admin/factures/{$invoice->id}/document")->assertNotFound();
    }
}
