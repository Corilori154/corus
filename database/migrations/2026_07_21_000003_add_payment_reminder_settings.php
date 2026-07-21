<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('payment_reminders_enabled')->default(false);
            $table->unsignedSmallInteger('payment_reminder_delay_days')->default(1);
            $table->unsignedSmallInteger('payment_reminder_interval_days')->default(7);
            $table->unsignedTinyInteger('payment_reminder_max_count')->default(3);
            $table->decimal('payment_reminder_fee', 8, 2)->default(0);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->decimal('reminder_fees_total', 8, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn(['reminder_count', 'last_reminder_at', 'reminder_fees_total']));
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn(['payment_reminders_enabled', 'payment_reminder_delay_days', 'payment_reminder_interval_days', 'payment_reminder_max_count', 'payment_reminder_fee']));
    }
};
