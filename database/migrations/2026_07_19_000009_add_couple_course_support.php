<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->boolean('couple_mode')->default(false)->after('is_active');
            $table->unsignedSmallInteger('max_role_gap')->nullable()->after('couple_mode');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('dance_role', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->dropColumn('dance_role'));
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropColumn(['couple_mode', 'max_role_gap']));
    }
};
