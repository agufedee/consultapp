<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Anyone authenticated can view the list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Nutricionista can only view their own patients (those they've attended).
     * Secretaria and Admin can view all.
     */
    public function view(User $user, Patient $patient): bool
    {
        // Admin and Secretaria can view all
        if ($user->isAdmin() || $user->isSecretaria()) {
            return true;
        }

        // Nutricionista can only view patients they've had appointments with
        if ($user->isNutricionista()) {
            return $patient->appointments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Nutricionista can create patients.
     * Secretaria can create patients.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Nutricionista', 'Secretaria']);
    }

    /**
     * Nutricionista can update only their own patients.
     * Secretaria can only update active field.
     * Admin can update everything.
     */
    public function update(User $user, Patient $patient): bool
    {
        // Admin can do anything
        if ($user->isAdmin()) {
            return true;
        }

        // Nutricionista can update their own patients
        if ($user->isNutricionista()) {
            return $patient->appointments()
                ->where('user_id', $user->id)
                ->exists();
        }

        // Secretaria can only manage patient active status
        if ($user->isSecretaria()) {
            return true;
        }

        return false;
    }

    /**
     * Only Admin can delete patients.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only Admin can force delete.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }
}
