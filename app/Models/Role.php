<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The users that belong to the role.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'role_user');
    }

    /**
     * The privileges that belong to the role.
     */
    public function privileges(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Privilege::class, 'privilege_role');
    }

    /**
     * Check if the role has a specific privilege.
     */
    public function hasPrivilege(string $privilegeName): bool
    {
        return $this->privileges()->where('name', $privilegeName)->exists();
    }
}
