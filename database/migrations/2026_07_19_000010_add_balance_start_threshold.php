<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('balance_after_count')->default(0)->after('max_role_gap');
        });
    }

    public function down(): void
    {
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropColumn('balance_after_count'));
    }
};
