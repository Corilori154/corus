<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may leave this empty table behind when a previous DDL statement fails.
        Schema::dropIfExists('dance_course_pricing_category');

        Schema::create('dance_course_pricing_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dance_course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->unique(['dance_course_id', 'pricing_category_id'], 'course_pricing_category_unique');
        });

        Schema::table('pricing_categories', fn (Blueprint $table) => $table->dropColumn('discount_percentage'));
    }

    public function down(): void
    {
        Schema::table('pricing_categories', fn (Blueprint $table) => $table->decimal('discount_percentage', 5, 2)->default(0));
        Schema::dropIfExists('dance_course_pricing_category');
    }
};
