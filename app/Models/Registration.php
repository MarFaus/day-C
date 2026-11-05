<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'registrations';

    public $timestamps = false;

    protected $fillable = ['attendee_id', 'ticket_id', 'registration_time'];

    protected $casts = ['registration_time' => 'datetime'];

    public function attendee()
    {
        return $this->belongsTo(Attendee::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function sessionRegistrations()
    {
        return $this->hasMany(SessionRegistration::class);
    }

    public function sessionIds(): array
    {
        return $this->sessionRegistrations()->orderBy('session_id')->pluck('session_id')->map(fn ($id) => (int) $id)->all();
    }
}
