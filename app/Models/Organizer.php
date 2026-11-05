<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    protected $table = 'organizers';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'email', 'password_hash'];

    protected $hidden = ['password_hash'];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
