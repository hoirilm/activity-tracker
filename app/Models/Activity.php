<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_parallel' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function getDurationAttribute()
    {
        if (!$this->end_time) {
            return null;
        }
        
        $diff = $this->end_time->diff($this->start_time);
        
        return sprintf('%02d:%02d:%02d', ($diff->days * 24) + $diff->h, $diff->i, $diff->s);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
