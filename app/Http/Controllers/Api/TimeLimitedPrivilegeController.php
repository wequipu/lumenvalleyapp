<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Privilege;
use App\Models\TimeLimitedPrivilege;
use App\Models\User;
use App\Services\TimeLimitedPrivilegeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeLimitedPrivilegeController extends Controller
{
    protected TimeLimitedPrivilegeService $timeLimitedPrivilegeService;

    public function __construct(TimeLimitedPrivilegeService $timeLimitedPrivilegeService)
    {
        $this->timeLimitedPrivilegeService = $timeLimitedPrivilegeService;
    }

    /**
     * Grant a time-limited privilege to a user
     */
    public function grant(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'privilege_id' => 'required|exists:privileges,id',
            'expires_at' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $privilege = Privilege::findOrFail($request->privilege_id);
        $expiresAt = Carbon::parse($request->expires_at);

        // Only Super Admin and Admin (who has been granted this specific ability) can grant time-limited privileges
        $currentUser = auth()->user();
        $canGrant = $currentUser->hasRole('Super Admin') ||
                   ($currentUser->hasRole('Admin') && $currentUser->hasPrivilege('grant-time-limited-privileges'));

        if (! $canGrant) {
            return response()->json(['message' => 'You do not have permission to grant time-limited privileges.'], 403);
        }

        // If current user is not Super Admin, they can only grant privileges they have themselves
        if (! $currentUser->hasRole('Super Admin')) {
            if (! $currentUser->hasPrivilege($privilege->name)) {
                return response()->json(['message' => 'You cannot grant a privilege you do not have yourself.'], 403);
            }
        }

        $timeLimitedPrivilege = $this->timeLimitedPrivilegeService->grantPrivilege(
            $user,
            $currentUser,
            $privilege,
            $expiresAt,
            $request->reason
        );

        $timeLimitedPrivilege->load(['user', 'privilege', 'grantedByUser']);

        return response()->json($timeLimitedPrivilege, 201);
    }

    /**
     * Get all active time-limited privileges for the authenticated user
     */
    public function getActiveForUser(): JsonResponse
    {
        $user = auth()->user();
        $activePrivileges = $this->timeLimitedPrivilegeService->getUserPrivileges($user);

        return response()->json($activePrivileges);
    }

    /**
     * Get all time-limited privileges (for Super Admin only)
     */
    public function getAll(): JsonResponse
    {
        $currentUser = auth()->user();
        if (! $currentUser->hasRole('Super Admin')) {
            return response()->json(['message' => 'Only Super Admin can view all time-limited privileges.'], 403);
        }

        $timeLimitedPrivileges = TimeLimitedPrivilege::with(['user', 'privilege', 'grantedByUser'])
            ->orderBy('expires_at', 'asc')
            ->get();

        return response()->json($timeLimitedPrivileges);
    }

    /**
     * Revoke a time-limited privilege
     */
    public function revoke(int $id): JsonResponse
    {
        $currentUser = auth()->user();
        $canRevoke = $currentUser->hasRole('Super Admin') ||
                    ($currentUser->hasPrivilege('manage-time-limited-privileges'));

        if (! $canRevoke) {
            return response()->json(['message' => 'You do not have permission to revoke time-limited privileges.'], 403);
        }

        try {
            $this->timeLimitedPrivilegeService->revokePrivilege($id, $currentUser);

            return response()->json(['message' => 'Time-limited privilege revoked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
