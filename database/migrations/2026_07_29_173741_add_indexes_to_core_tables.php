<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('project_id');
            $table->index('category_id');
            $table->index('start_time');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['start_time']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
