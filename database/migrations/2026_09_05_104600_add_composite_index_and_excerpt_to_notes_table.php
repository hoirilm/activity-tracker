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
        Schema::table('notes', function (Blueprint $table) {
            $table->string('excerpt', 255)->nullable()->after('title');
            $table->index(['user_id', 'is_archived', 'is_pinned', 'updated_at'], 'notes_user_archive_pinned_updated_idx');
        });

        // Populate excerpt for existing notes
        \App\Models\Note::chunk(100, function ($notes) {
            foreach ($notes as $note) {
                $clean = strip_tags($note->content ?? '');
                $clean = preg_replace('/```[\s\S]*?```/', '', $clean);
                $clean = preg_replace('/`[^`]*`/', '', $clean);
                $clean = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $clean);
                $clean = preg_replace('/[#\*\_~`>\[\]\(\)\-\+\|]/', ' ', $clean);
                $clean = preg_replace('/\s+/', ' ', $clean);
                $clean = trim($clean);
                $note->update(['excerpt' => \Illuminate\Support\Str::limit($clean, 120)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_user_archive_pinned_updated_idx');
            $table->dropColumn('excerpt');
        });
    }
};
