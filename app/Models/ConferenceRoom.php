<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConferenceRoom extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'room_number',
        'capacity',
        'hourly_rate',
        'daily_rate',
        'equipment',
        'is_air_conditioned',
        'photo_path',
        'status',
    ];

    /**
     * Get all of the conference room's reservations.
     */
    public function reservations(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Reservation::class, 'reservable');
    }
}
