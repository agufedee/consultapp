<?php

namespace App\Rules;

use App\Models\Appointment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOverlappingAppointments implements ValidationRule
{
    public function __construct(
        public ?int $userId = null,
        public ?int $excludeAppointmentId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userId = $this->userId ?? request()->input('user_id');
        $excludeId = $this->excludeAppointmentId ?? request()->input('exclude_appointment_id');
        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');

        if (! $userId || ! $startDate || ! $endDate) {
            return; // Other validations will catch missing fields
        }

        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        $overlapping = Appointment::query()
            ->where('user_id', $userId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($query) use ($startDate, $endDate) {
                // New appointment starts during existing
                $query->where(function ($q) use ($startDate) {
                    $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>', $startDate);
                })
                // Or new appointment ends during existing
                    ->orWhere(function ($q) use ($endDate) {
                        $q->where('start_date', '<', $endDate)
                            ->where('end_date', '>=', $endDate);
                    })
                // Or new appointment contains existing
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '>=', $startDate)
                            ->where('end_date', '<=', $endDate);
                    });
            })
            ->exists();

        if ($overlapping) {
            $fail('El profesional ya tiene un turno en ese horario.');
        }
    }
}
