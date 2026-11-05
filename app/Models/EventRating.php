<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRating extends Model
{
    protected $table = 'event_ratings';

    public $timestamps = false;

    protected $fillable = ['attendee_id', 'event_id', 'rating', 'created_at'];
}
