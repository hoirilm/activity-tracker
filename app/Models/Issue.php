<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTicketIdAttribute()
    {
        return sprintf('TKT-%04d', $this->id);
    }

    public function getFormattedTitleAttribute()
    {
        return "[{$this->ticket_id}] - {$this->title}";
    }
}
