<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dance_course_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 150);
            $table->date('start_date');
            $table->unsignedSmallInteger('lessons_count');
            $table->decimal('amount', 8, 2);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->unique(['dance_course_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
