<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->boolean('trial_is_free')->default(true)->after('session_price');
            $table->decimal('trial_price', 8, 2)->default(0)->after('trial_is_free');
        });
        Schema::table('trial_requests', function (Blueprint $table) {
            $table->boolean('trial_is_free')->default(true)->after('preferred_date');
            $table->decimal('trial_price', 8, 2)->default(0)->after('trial_is_free');
        });
    }

    public function down(): void
    {
        Schema::table('trial_requests', fn (Blueprint $table) => $table->dropColumn(['trial_is_free', 'trial_price']));
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropColumn(['trial_is_free', 'trial_price']));
    }
};
