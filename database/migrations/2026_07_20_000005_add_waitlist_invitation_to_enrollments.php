<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('waitlist_token_hash', 64)->nullable()->after('status');
            $table->timestamp('waitlist_invited_at')->nullable()->after('waitlist_token_hash');
            $table->timestamp('waitlist_invitation_expires_at')->nullable()->after('waitlist_invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->dropColumn(['waitlist_token_hash', 'waitlist_invited_at', 'waitlist_invitation_expires_at']));
    }
};
