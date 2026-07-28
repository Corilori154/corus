<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->boolean('trial_enabled')->default(true)->after('session_price');
            $table->boolean('trial_payment_on_site')->default(false)->after('trial_price');
        });

        Schema::table('trial_requests', function (Blueprint $table) {
            $table->boolean('trial_payment_on_site')->default(false)->after('trial_price');
        });
    }

    public function down(): void
    {
        Schema::table('trial_requests', fn (Blueprint $table) => $table->dropColumn('trial_payment_on_site'));
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropColumn(['trial_enabled', 'trial_payment_on_site']));
    }
};
