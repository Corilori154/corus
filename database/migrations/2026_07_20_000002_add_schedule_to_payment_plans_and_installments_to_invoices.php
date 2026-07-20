<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('payment_plans', 'schedule_mode')) {
            Schema::table('payment_plans', fn (Blueprint $table) => $table->string('schedule_mode')->default('evenly_spaced')->after('installment_count'));
        }
        if (! Schema::hasColumn('invoices', 'installment_number')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->index('enrollment_id', 'invoices_enrollment_id_index'));
            Schema::table('invoices', fn (Blueprint $table) => $table->dropUnique(['enrollment_id']));
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedSmallInteger('installment_number')->default(1)->after('enrollment_id');
                $table->unsignedSmallInteger('installment_count')->default(1)->after('installment_number');
                $table->unique(['enrollment_id', 'installment_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'installment_number']);
            $table->dropColumn(['installment_number', 'installment_count']);
            $table->unique('enrollment_id');
            $table->dropIndex('invoices_enrollment_id_index');
        });
        Schema::table('payment_plans', fn (Blueprint $table) => $table->dropColumn('schedule_mode'));
    }
};
