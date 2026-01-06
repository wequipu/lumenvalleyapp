<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Role::class, 'role_user');
    }

    /**
     * The time-limited privileges granted to the user.
     */
    public function timeLimitedPrivileges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TimeLimitedPrivilege::class, 'user_id');
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if the user has a specific privilege.
     */
    public function hasPrivilege(string $privilegeName): bool
    {
        // Super Admin has all privileges
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        // Check role-based privileges
        foreach ($this->roles as $role) {
            if ($role->hasPrivilege($privilegeName)) {
                return true;
            }
        }

        // Check time-limited privileges
        $hasTimeLimitedPrivilege = $this->timeLimitedPrivileges()
            ->where('expires_at', '>', now())
            ->whereHas('privilege', function ($query) use ($privilegeName) {
                $query->where('name', $privilegeName);
            })
            ->exists();

        return $hasTimeLimitedPrivilege;
    }
}
