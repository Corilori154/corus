<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('course_count');
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
            $table->unique(['school_id', 'course_count']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('base_amount', 8, 2)->nullable()->after('lessons_count');
            $table->decimal('discount_amount', 8, 2)->default(0)->after('base_amount');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');
        });

        DB::table('enrollments')->update(['base_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->dropColumn(['base_amount', 'discount_amount', 'discount_percentage']));
        Schema::dropIfExists('discount_rules');
    }
};
