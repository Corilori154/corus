<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['school_locations', 'dance_disciplines', 'dance_levels'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        Schema::create('pricing_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        Schema::table('dance_courses', function (Blueprint $table) {
            $table->foreignId('school_location_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
            $table->foreignId('dance_discipline_id')->nullable()->after('school_location_id')->constrained()->nullOnDelete();
            $table->foreignId('dance_level_id')->nullable()->after('dance_discipline_id')->constrained()->nullOnDelete();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('pricing_category_id')->nullable()->after('dance_course_id')->constrained()->nullOnDelete();
            $table->string('pricing_category_name')->nullable()->after('pricing_category_id');
            $table->decimal('category_discount_amount', 8, 2)->default(0)->after('base_amount');
        });

        foreach (DB::table('dance_courses')->select('school_id')->distinct()->pluck('school_id') as $schoolId) {
            $this->backfill($schoolId, 'location', 'school_locations', 'school_location_id');
            $this->backfill($schoolId, 'style', 'dance_disciplines', 'dance_discipline_id');
            $this->backfill($schoolId, 'level', 'dance_levels', 'dance_level_id');
        }
    }

    private function backfill(int $schoolId, string $source, string $table, string $foreignKey): void
    {
        $values = DB::table('dance_courses')->where('school_id', $schoolId)->whereNotNull($source)->select($source)->distinct()->pluck($source);
        foreach ($values as $value) {
            $id = DB::table($table)->insertGetId(['school_id' => $schoolId, 'name' => $value, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('dance_courses')->where('school_id', $schoolId)->where($source, $value)->update([$foreignKey => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_category_id');
            $table->dropColumn(['pricing_category_name', 'category_discount_amount']);
        });
        Schema::table('dance_courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dance_level_id');
            $table->dropConstrainedForeignId('dance_discipline_id');
            $table->dropConstrainedForeignId('school_location_id');
        });
        Schema::dropIfExists('pricing_categories');
        Schema::dropIfExists('dance_levels');
        Schema::dropIfExists('dance_disciplines');
        Schema::dropIfExists('school_locations');
    }
};
