<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', fn (Blueprint $table) => $table->unsignedSmallInteger('waitlist_invitation_hours')->default(72)->after('balance_after_count'));
    }

    public function down(): void
    {
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropColumn('waitlist_invitation_hours'));
    }
};
