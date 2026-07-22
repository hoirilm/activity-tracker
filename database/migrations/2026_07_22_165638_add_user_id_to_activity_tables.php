<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Truncate existing data to prevent constraint violations
        DB::table('activities')->truncate();
        DB::table('projects')->delete(); // delete instead of truncate for SQLite foreign key constraint friendliness if needed, though Laravel handles it.
        DB::table('categories')->delete();
        
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
