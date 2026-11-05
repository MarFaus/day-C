<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';

    public $timestamps = false;

    protected $fillable = ['event_id', 'name'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class)->orderBy('id');
    }
}
