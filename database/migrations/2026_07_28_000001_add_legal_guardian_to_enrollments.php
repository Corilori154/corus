<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('is_minor')->default(false)->after('phone');
            $table->string('legal_guardian_first_name', 80)->nullable()->after('is_minor');
            $table->string('legal_guardian_last_name', 80)->nullable()->after('legal_guardian_first_name');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['is_minor', 'legal_guardian_first_name', 'legal_guardian_last_name']);
        });
    }
};
