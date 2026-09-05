<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%");
        });
    }

    public function getCleanContentAttribute(): string
    {
        if (! $this->content) {
            return '';
        }

        // Strip HTML tags for clean card excerpt and search preview
        $clean = strip_tags($this->content);

        // Ultra-fast markdown stripping without invoking heavy CommonMark AST parser
        $clean = preg_replace('/```[\s\S]*?```/', '', $clean);
        $clean = preg_replace('/`[^`]*`/', '', $clean);
        $clean = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $clean);
        $clean = preg_replace('/[#\*\_~`>\[\]\(\)\-\+\|]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }

    protected static function booted(): void
    {
        static::saving(function (Note $note) {
            if ($note->isDirty('content') || empty($note->attributes['excerpt'])) {
                $note->attributes['excerpt'] = Str::limit($note->clean_content, 120);
            }
        });
    }

    public function getExcerptAttribute(): string
    {
        if (isset($this->attributes['excerpt']) && $this->attributes['excerpt'] !== '') {
            return $this->attributes['excerpt'];
        }

        return Str::limit($this->clean_content, 120);
    }

    public function getWordCountAttribute(): int
    {
        if (! $this->content) {
            return 0;
        }

        return str_word_count(strip_tags($this->content));
    }

    public function getReadingTimeAttribute(): string
    {
        $words = $this->word_count;
        $minutes = ceil($words / 200);

        return $minutes <= 1 ? '< 1 min' : "{$minutes} min";
    }
}
