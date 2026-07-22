<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('schools', function (Blueprint $table) { $table->string('contact_button_label', 80)->default('Nous contacter'); $table->string('contact_button_url', 1000)->nullable(); }); }
    public function down(): void { Schema::table('schools', fn (Blueprint $table) => $table->dropColumn(['contact_button_label', 'contact_button_url'])); }
};
