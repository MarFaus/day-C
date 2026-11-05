<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = ['channel_id', 'name', 'capacity'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function sessions()
    {
        return $this->hasMany(EventSession::class)->orderBy('start');
    }
}
