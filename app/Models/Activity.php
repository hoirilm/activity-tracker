<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds' => 'integer',
            'is_parallel' => 'boolean',
        ];
    }

    public function isPaused(): bool
    {
        return ! is_null($this->paused_at);
    }

    public function getFormattedPauseDurationAttribute(): ?string
    {
        if (! $this->paused_seconds || $this->paused_seconds <= 0) {
            return null;
        }

        $hours = floor($this->paused_seconds / 3600);
        $minutes = floor(($this->paused_seconds % 3600) / 60);
        $seconds = $this->paused_seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        if ($minutes > 0) {
            return "{$minutes}m";
        }

        return "{$seconds}s";
    }

    public function getFormattedStartTimeAttribute(): string
    {
        return $this->start_time ? $this->start_time->format('H:i:s') : '';
    }

    public function getFormattedEndTimeAttribute(): string
    {
        return $this->end_time ? $this->end_time->format('H:i:s') : 'now';
    }

    public function getElapsedSecondsAttribute(): int
    {
        if ($this->end_time) {
            $total = $this->start_time->diffInSeconds($this->end_time) - ($this->paused_seconds ?? 0);
            return max(0, (int) $total);
        }

        $endTime = $this->paused_at ?: now();
        $total = $this->start_time->diffInSeconds($endTime) - ($this->paused_seconds ?? 0);
        return max(0, (int) $total);
    }

    public function getDurationAttribute()
    {
        if (! $this->end_time) {
            return null;
        }

        $totalSeconds = $this->getElapsedSecondsAttribute();

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
