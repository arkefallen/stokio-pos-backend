<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteUser
{
    /**
     * Delete a user (admin only)
     *
     * @param User $user The user to delete
     * @param User $deletedBy The admin performing the deletion
     * @return bool
     * @throws AuthorizationException
     */
    public function execute(User $user, User $deletedBy): bool
    {
        // Only admins can delete users
        if (!$deletedBy->isAdmin()) {
            throw new AuthorizationException('Only administrators can delete users.');
        }

        // Prevent self-deletion
        if ($user->id === $deletedBy->id) {
            throw new AuthorizationException('You cannot delete your own account.');
        }

        return DB::transaction(function () use ($user) {
            // Revoke all tokens first
            $user->tokens()->delete();

            // Delete the user
            return $user->delete();
        });
    }
}
