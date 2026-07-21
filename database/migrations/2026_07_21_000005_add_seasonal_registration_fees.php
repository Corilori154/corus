<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('schools', function (Blueprint $table) { $table->boolean('registration_fee_enabled')->default(false); $table->string('registration_fee_name')->default('Frais d’inscription'); $table->decimal('registration_fee_amount', 8, 2)->default(0); });
        Schema::table('enrollments', function (Blueprint $table) { $table->string('registration_fee_name')->nullable(); $table->decimal('registration_fee_amount', 8, 2)->default(0); });
    }
    public function down(): void { Schema::table('enrollments', fn (Blueprint $table) => $table->dropColumn(['registration_fee_name', 'registration_fee_amount'])); Schema::table('schools', fn (Blueprint $table) => $table->dropColumn(['registration_fee_enabled', 'registration_fee_name', 'registration_fee_amount'])); }
};
