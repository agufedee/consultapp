<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Anyone authenticated can view the list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Nutricionista can view their own appointments.
     * Secretaria and Admin can view all.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasAnyRole(['Secretaria', 'Admin'])) {
            return true;
        }

        return $appointment->user_id === $user->id;
    }

    /**
     * Nutricionista can create appointments.
     * Secretaria can create appointments.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Nutricionista', 'Secretaria']);
    }

    /**
     * Nutricionista can update their own appointments only.
     * Secretaria cannot update.
     * Admin can update everything.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Admin can do anything
        if ($user->role->name === 'Admin') {
            return true;
        }

        // Nutricionista can only update their own appointments
        if ($user->role->name === 'Nutricionista') {
            return $appointment->user_id === $user->id;
        }

        // Secretaria cannot update appointments
        return false;
    }

    /**
     * Only Admin can delete appointments.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->role->name === 'Admin';
    }

    /**
     * Only Admin can force delete.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->role->name === 'Admin';
    }
}
