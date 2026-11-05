<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'event_tickets';

    public $timestamps = false;

    protected $fillable = ['event_id', 'name', 'cost', 'special_validity'];

    protected $casts = ['cost' => 'float'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'ticket_id');
    }

    public function validity(): ?array
    {
        if (! $this->special_validity) {
            return null;
        }

        $decoded = json_decode($this->special_validity, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * The reference date can be fixed with RULES_AS_OF so the bundled
     * acceptance-test dataset keeps its original meaning (September 2019).
     */
    public static function referenceDate(): Carbon
    {
        $asOf = config('app.rules_as_of');

        return $asOf ? Carbon::parse($asOf)->startOfDay() : Carbon::today();
    }

    public function isAvailable(): bool
    {
        $validity = $this->validity();

        if (! $validity) {
            return true;
        }

        if (($validity['type'] ?? null) === 'date') {
            $until = Carbon::parse($validity['date'])->endOfDay();

            return self::referenceDate()->lessThanOrEqualTo($until);
        }

        if (($validity['type'] ?? null) === 'amount') {
            return $this->registrations()->count() < (int) ($validity['amount'] ?? 0);
        }

        return true;
    }

    public function description(): ?string
    {
        $validity = $this->validity();

        if (! $validity) {
            return null;
        }

        if (($validity['type'] ?? null) === 'date') {
            return 'Available until '.Carbon::parse($validity['date'])->format('F j, Y');
        }

        if (($validity['type'] ?? null) === 'amount') {
            return (int) $validity['amount'].' tickets available';
        }

        return null;
    }

    public function displayDescription(): ?string
    {
        return $this->description();
    }
}
