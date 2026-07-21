<?php

namespace Tests\Feature;

use App\Models\DanceCourse;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Notifications\PaymentReminder;
use App\Services\PaymentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_invoice_receives_reminder_and_optional_fee(): void
    {
        Notification::fake();
        $course = DanceCourse::factory()->create();
        $school = $course->school;
        $school->update([
            'payment_reminders_enabled' => true, 'payment_reminder_delay_days' => 0,
            'payment_reminder_interval_days' => 7, 'payment_reminder_max_count' => 3,
            'payment_reminder_fee' => 10,
        ]);
        $enrollment = Enrollment::create([
            'school_id' => $school->id, 'dance_course_id' => $course->id,
            'first_name' => 'Lina', 'last_name' => 'Test', 'email' => 'lina@example.ch',
            'start_date' => '2026-09-01', 'lessons_count' => 10,
            'base_amount' => 100, 'amount' => 100, 'status' => 'accepted',
        ]);
        $invoice = Invoice::create([
            'school_id' => $school->id, 'enrollment_id' => $enrollment->id,
            'number' => 'FAC-TEST', 'amount' => 100,
            'issued_at' => today()->subDays(10), 'due_at' => today()->subDay(),
        ]);

        $this->assertSame(1, app(PaymentReminderService::class)->process());

        $invoice->refresh();
        $this->assertSame('110.00', $invoice->amount);
        $this->assertSame('10.00', $invoice->reminder_fees_total);
        $this->assertSame(1, $invoice->reminder_count);
        Notification::assertSentOnDemand(PaymentReminder::class);
    }
}
