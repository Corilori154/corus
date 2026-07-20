<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('accent', 20)->default('#ef6f7f');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('dance_courses', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'Tempo Studio',
            'slug' => 'tempo',
            'email' => 'admin@tempo.test',
            'city' => 'Genève',
            'accent' => '#ef6f7f',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update(['school_id' => $schoolId]);
        DB::table('dance_courses')->update(['school_id' => $schoolId]);
    }

    public function down(): void
    {
        Schema::table('dance_courses', fn (Blueprint $table) => $table->dropConstrainedForeignId('school_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('school_id'));
        Schema::dropIfExists('schools');
    }
};
