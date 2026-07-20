<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('installment_count');
            $table->string('adjustment_direction', 20)->default('fee');
            $table->string('adjustment_mode', 20)->default('fixed');
            $table->decimal('adjustment_value', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('payment_plan_id')->nullable()->after('pricing_category_id')->constrained()->nullOnDelete();
            $table->string('payment_plan_name')->nullable()->after('payment_plan_id');
            $table->unsignedSmallInteger('installment_count')->default(1)->after('payment_plan_name');
            $table->decimal('payment_adjustment_amount', 8, 2)->default(0)->after('discount_percentage');
            $table->decimal('installment_amount', 8, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_plan_id');
            $table->dropColumn(['payment_plan_name', 'installment_count', 'payment_adjustment_amount', 'installment_amount']);
        });
        Schema::dropIfExists('payment_plans');
    }
};
