<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('billing_name')->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_house_number', 20)->nullable();
            $table->string('billing_postal_code', 16)->nullable();
            $table->string('billing_city')->nullable();
            $table->char('billing_country', 2)->default('CH');
            $table->string('billing_iban', 34)->nullable();
            $table->string('invoice_prefix', 12)->default('FAC');
            $table->unsignedSmallInteger('invoice_due_days')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn([
            'billing_name', 'billing_street', 'billing_house_number', 'billing_postal_code',
            'billing_city', 'billing_country', 'billing_iban', 'invoice_prefix', 'invoice_due_days',
        ]));
    }
};
