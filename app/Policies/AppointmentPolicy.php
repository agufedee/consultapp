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
     * Admin/Nutricionista can view all appointments.
     * Secretaria can view all.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasAnyRole(['Secretaria', 'Admin', 'Nutricionista'])) {
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
     * Admin/Nutricionista can update any appointment.
     * Secretaria cannot update.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Admin and Nutricionista can do anything
        if ($user->isAdmin() || $user->isNutricionista()) {
            return true;
        }

        // Secretaria cannot update appointments
        return false;
    }

    /**
     * Admin/Nutricionista can delete any appointment.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin() || $user->isNutricionista();
    }

    /**
     * Admin/Nutricionista can force delete.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin() || $user->isNutricionista();
    }
}
