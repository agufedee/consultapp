<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAppointmentDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $startDate = \Carbon\Carbon::parse($value);

        // BR-001: Cannot be in the past
        if ($startDate->isPast()) {
            $fail('No se pueden agendar turnos en el pasado.');

            return;
        }

        // BR-003: Must be within business hours (7:00 - 21:00)
        $hour = $startDate->hour;
        if ($hour < 7 || $hour >= 21) {
            $fail('Los turnos deben agendarse entre las 07:00 y las 21:00.');
        }
    }
}
