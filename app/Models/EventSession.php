<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $fillable = ['room_id', 'title', 'description', 'speaker', 'start', 'end', 'type', 'cost'];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'cost' => 'float',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function channel()
    {
        return $this->room->channel;
    }

    public function event()
    {
        return $this->room->channel->event;
    }

    public function registrations()
    {
        return $this->hasMany(SessionRegistration::class, 'session_id');
    }

    public function attendeeCount(): int
    {
        if ($this->type === 'talk') {
            return $this->event()->registrationCount();
        }

        return $this->registrations()->count();
    }
}
