<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dance_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('style');
            $table->string('level');
            $table->string('day');
            $table->string('time');
            $table->string('teacher');
            $table->unsignedSmallInteger('places');
            $table->unsignedSmallInteger('capacity');
            $table->decimal('price', 8, 2);
            $table->string('accent', 20)->default('#ef6f7f');
            $table->text('image');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dance_courses');
    }
};
