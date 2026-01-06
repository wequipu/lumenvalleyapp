<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'id_type',
        'id_number',
        'id_photo_path',
        'date_enregistrement',
        'identity_document_type',
        'identity_document_number',
        'identity_document_photo_path',
    ];

    protected $appends = ['id_photo_url'];

    /**
     * Get the reservations for the client.
     */
    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Reservation::class);
    }

    public function getIdPhotoUrlAttribute(): ?string
    {
        if ($this->id_photo_path) {
            return url('/api/clients/identity-photo/'.$this->id_photo_path);
        }

        return null;
    }
}
