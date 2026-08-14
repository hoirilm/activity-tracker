<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public const STATUS_NEW = 'new';

    public const STATUS_ON_PROGRESS = 'on_progress';

    public const STATUS_DONE = 'done';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_ARCHIVED = 'archived';

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && $this->status !== self::STATUS_DONE;
    }

    public function isDueToday(): bool
    {
        return $this->due_at && $this->due_at->isToday() && $this->status !== self::STATUS_DONE;
    }

    public function isDueSoon(): bool
    {
        return $this->due_at && $this->due_at->isFuture() && $this->due_at->diffInDays(now()) <= 7 && $this->status !== self::STATUS_DONE;
    }

    public function getDueBadgeAttribute(): ?array
    {
        if (! $this->due_at) {
            return null;
        }

        if ($this->status === self::STATUS_DONE) {
            return [
                'type' => 'done',
                'label' => 'Completed ('.$this->due_at->format('M d').')',
                'color' => 'zinc',
                'icon' => 'check-circle',
            ];
        }

        if ($this->due_at->isPast()) {
            return [
                'type' => 'overdue',
                'label' => 'Overdue ('.$this->due_at->diffForHumans(['parts' => 1, 'short' => true]).')',
                'color' => 'rose',
                'icon' => 'exclamation-circle',
            ];
        }

        if ($this->due_at->isToday()) {
            return [
                'type' => 'today',
                'label' => 'Due Today '.($this->due_at->format('H:i') !== '00:00' ? $this->due_at->format('H:i') : ''),
                'color' => 'amber',
                'icon' => 'clock',
            ];
        }

        if ($this->due_at->isTomorrow()) {
            return [
                'type' => 'tomorrow',
                'label' => 'Due Tomorrow '.($this->due_at->format('H:i') !== '00:00' ? $this->due_at->format('H:i') : ''),
                'color' => 'indigo',
                'icon' => 'calendar',
            ];
        }

        return [
            'type' => 'future',
            'label' => $this->due_at->format('M d, Y'),
            'color' => 'sky',
            'icon' => 'calendar',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function checklists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('position')->orderBy('id');
    }

    public function getChecklistStatsAttribute(): ?array
    {
        $total = $this->checklists->count();
        if ($total === 0) {
            return null;
        }

        $completed = $this->checklists->where('is_completed', true)->count();
        $percent = (int) round(($completed / $total) * 100);

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $percent,
            'is_all_completed' => $completed === $total,
        ];
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
