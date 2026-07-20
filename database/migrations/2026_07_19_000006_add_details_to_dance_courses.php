<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->string('location')->nullable()->after('teacher');
            $table->text('description')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropColumn(['location', 'description']));
    }
};
