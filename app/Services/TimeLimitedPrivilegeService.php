<?php

namespace App\Services;

use App\Models\Privilege;
use App\Models\TimeLimitedPrivilege;
use App\Models\User;
use Carbon\Carbon;

class TimeLimitedPrivilegeService
{
    public function grantPrivilege(User $user, User $grantedByUser, Privilege $privilege, Carbon $expiresAt, ?string $reason = null): TimeLimitedPrivilege
    {
        return TimeLimitedPrivilege::create([
            'user_id' => $user->id,
            'granted_by_user_id' => $grantedByUser->id,
            'privilege_id' => $privilege->id,
            'expires_at' => $expiresAt,
            'reason' => $reason,
        ]);
    }

    public function getUserPrivileges(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return TimeLimitedPrivilege::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->with(['privilege', 'grantedByUser'])
            ->get();
    }

    public function hasPrivilege(User $user, string $privilegeName): bool
    {
        $timeLimitedPrivilege = TimeLimitedPrivilege::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->whereHas('privilege', function ($query) use ($privilegeName) {
                $query->where('name', $privilegeName);
            })
            ->first();

        return $timeLimitedPrivilege !== null;
    }

    public function revokePrivilege(int $id, User $revokedByUser): bool
    {
        $timeLimitedPrivilege = TimeLimitedPrivilege::findOrFail($id);

        // Only the user who granted the privilege or a Super Admin can revoke it
        $isSuperAdmin = $revokedByUser->roles()->where('name', 'Super Admin')->exists();
        $isGrantedByUser = $timeLimitedPrivilege->granted_by_user_id === $revokedByUser->id;

        if (! $isSuperAdmin && ! $isGrantedByUser) {
            throw new \Exception('You are not authorized to revoke this time-limited privilege');
        }

        return $timeLimitedPrivilege->delete();
    }

    public function cleanupExpired(): int
    {
        return TimeLimitedPrivilege::where('expires_at', '<', now())->delete();
    }
}
