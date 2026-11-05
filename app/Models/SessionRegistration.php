<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionRegistration extends Model
{
    protected $table = 'session_registrations';

    public $timestamps = false;

    protected $fillable = ['registration_id', 'session_id'];
}
