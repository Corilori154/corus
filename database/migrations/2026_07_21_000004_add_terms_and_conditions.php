<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->longText('terms_and_conditions')->nullable());
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable();
            $table->char('terms_content_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->dropColumn(['terms_accepted_at', 'terms_content_hash']));
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('terms_and_conditions'));
    }
};
