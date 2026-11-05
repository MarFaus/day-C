<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionRating extends Model
{
    protected $table = 'session_ratings';

    public $timestamps = false;

    protected $fillable = ['attendee_id', 'session_id', 'rating', 'created_at'];
}
