<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendee extends Model
{
    protected $table = 'attendees';

    public $timestamps = false;

    protected $fillable = ['firstname', 'lastname', 'username', 'email', 'registration_code', 'login_token'];

    protected $hidden = ['login_token'];

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
}
