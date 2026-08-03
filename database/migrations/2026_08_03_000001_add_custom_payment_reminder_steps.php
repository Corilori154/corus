<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->json('payment_reminder_steps')->nullable()->after('payment_reminder_fee');
        });

        DB::table('schools')->orderBy('id')->each(function ($school) {
            $steps = [];
            for ($index = 0; $index < (int) $school->payment_reminder_max_count; $index++) {
                $steps[] = [
                    'delay_days' => (int) $school->payment_reminder_delay_days + ($index * (int) $school->payment_reminder_interval_days),
                    'fee' => round((float) $school->payment_reminder_fee, 2),
                ];
            }
            DB::table('schools')->where('id', $school->id)->update(['payment_reminder_steps' => json_encode($steps)]);
        });
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('payment_reminder_steps'));
    }
};
