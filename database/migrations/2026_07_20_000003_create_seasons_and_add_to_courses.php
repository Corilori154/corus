<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        Schema::table('dance_courses', function (Blueprint $table) {
            $table->foreignId('season_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropConstrainedForeignId('season_id'));
        Schema::dropIfExists('seasons');
    }
};
