<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_rules', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('percentage')->after('course_count');
            $table->decimal('fixed_amount', 10, 2)->default(0)->after('percentage');
        });
    }

    public function down(): void
    {
        Schema::table('discount_rules', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'fixed_amount']);
        });
    }
};
