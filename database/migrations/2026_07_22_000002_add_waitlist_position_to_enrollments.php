<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('waitlist_position')->nullable()->after('status')->index();
        });

        DB::table('enrollments')
            ->whereIn('status', ['waitlist', 'invited', 'expired'])
            ->orderBy('dance_course_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'dance_course_id'])
            ->groupBy('dance_course_id')
            ->each(function ($enrollments) {
                foreach ($enrollments->values() as $index => $enrollment) {
                    DB::table('enrollments')->where('id', $enrollment->id)->update([
                        'waitlist_position' => $index + 1,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('waitlist_position');
        });
    }
};
