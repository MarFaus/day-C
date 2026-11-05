<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    public $timestamps = false;

    protected $fillable = ['organizer_id', 'name', 'slug', 'date'];

    protected $casts = ['date' => 'date'];

    public function organizer()
    {
        return $this->belongsTo(Organizer::class);
    }

    public function channels()
    {
        return $this->hasMany(Channel::class)->orderBy('id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class)->orderBy('id');
    }

    public function sessions()
    {
        return $this->hasManyThrough(EventSession::class, Room::class, 'channel_id', 'room_id', 'id', 'id')
            ->join('channels', 'channels.id', '=', 'rooms.channel_id')
            ->where('channels.event_id', $this->id)
            ->select('sessions.*');
    }

    public function registrations()
    {
        return Registration::query()
            ->join('event_tickets', 'event_tickets.id', '=', 'registrations.ticket_id')
            ->where('event_tickets.event_id', $this->id);
    }

    public function registrationCount(): int
    {
        return Registration::query()
            ->join('event_tickets', 'event_tickets.id', '=', 'registrations.ticket_id')
            ->where('event_tickets.event_id', $this->id)
            ->count();
    }
}
