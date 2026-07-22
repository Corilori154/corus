<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->decimal('waitlist_invitation_hours', 5, 2)->default(72)->change();
        });
    }

    public function down(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('waitlist_invitation_hours')->default(72)->change();
        });
    }
};
