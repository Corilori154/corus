<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('time');
            $table->date('end_date')->nullable()->after('start_date');
            $table->decimal('session_price', 8, 2)->nullable()->after('price');
        });

        DB::table('dance_courses')->update([
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'session_price' => DB::raw('price * 30'),
        ]);

        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dance_course_id')->constrained()->cascadeOnDelete();
            $table->date('lesson_date');
            $table->timestamps();
            $table->unique(['dance_course_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'session_price']);
        });
    }
};
