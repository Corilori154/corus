<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('dance_courses')->whereNull('season_id')->select('school_id')->distinct()->pluck('school_id')->each(function ($schoolId) {
            $courses = DB::table('dance_courses')->where('school_id', $schoolId)->whereNull('season_id');
            $start = $courses->min('start_date');
            $end = $courses->max('end_date');
            if (! $start || ! $end) return;

            $seasonId = DB::table('seasons')->insertGetId([
                'school_id' => $schoolId, 'name' => 'Saison existante (migration)',
                'start_date' => $start, 'end_date' => $end, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('dance_courses')->where('school_id', $schoolId)->whereNull('season_id')->update(['season_id' => $seasonId]);
        });
    }

    public function down(): void
    {
        $ids = DB::table('seasons')->where('name', 'Saison existante (migration)')->pluck('id');
        DB::table('dance_courses')->whereIn('season_id', $ids)->update(['season_id' => null]);
        DB::table('seasons')->whereIn('id', $ids)->delete();
    }
};
