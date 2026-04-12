<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Only Admin can view roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only Admin can view individual role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only Admin can create roles.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only Admin can update roles.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only Admin can delete roles.
     * Cannot delete roles that have users.
     */
    public function delete(User $user, Role $role): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // Cannot delete role if it has users
        if ($role->users()->exists()) {
            return false;
        }

        return true;
    }
}
