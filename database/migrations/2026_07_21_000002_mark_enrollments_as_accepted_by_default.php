<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('enrollments')->where('status', 'pending')->update(['status' => 'accepted']);
        Schema::table('enrollments', fn (Blueprint $table) => $table->string('status', 30)->default('accepted')->change());
    }

    public function down(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->string('status', 30)->default('pending')->change());
        DB::table('enrollments')->where('status', 'accepted')->update(['status' => 'pending']);
    }
};
