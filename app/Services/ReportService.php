<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Get new patients within a date range.
     * A new patient is one whose FIRST ATTENDED appointment falls in the date range.
     *
     * @return Collection Appointments representing new patients (one per patient)
     */
    public function getNewPatients(Carbon $startDate, Carbon $endDate): Collection
    {
        // Get all ATTENDED appointments in the date range
        $appointments = Appointment::where('status', AppointmentStatus::ATENDIDO->value)
            ->whereBetween('start_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with('patient')
            ->get();

        // Group by patient and keep only the first (earliest) appointment per patient
        return $appointments
            ->groupBy('patient_id')
            ->map(fn ($group) => $group->sortBy('start_date')->first())
            ->values();
    }

    /**
     * Get patient retention metrics within a cohort and retention window.
     * Cohort: patients whose FIRST ATTENDED appointment is in the start/end date range.
     * Retention: has a SECOND ATTENDED appointment within N days of the first.
     *
     * @param  int  $days  Retention window (default 30 days)
     * @return Collection Patient retention records with status (retained/not retained)
     */
    public function getPatientRetention(Carbon $startDate, Carbon $endDate, int $days = 30): Collection
    {
        // Build the cohort: patients whose first ATTENDED appointment is in the date range
        $cohort = $this->getNewPatients($startDate, $endDate);

        if ($cohort->isEmpty()) {
            return collect();
        }

        $cohortPatientIds = $cohort->pluck('patient_id')->unique();
        $retentionDeadline = $startDate->copy()->addDays($days);

        // For each patient in the cohort, check if they have a second ATTENDED appointment
        // within N days of their first appointment
        $retentionData = $cohort->map(function ($firstAppointment) use ($days) {
            $patient = $firstAppointment->patient;
            $cohortEndDate = $firstAppointment->start_date->copy()->addDays($days);

            // Get the second ATTENDED appointment after the first one within the retention window
            $secondAppointment = Appointment::where('patient_id', $firstAppointment->patient_id)
                ->where('status', AppointmentStatus::ATENDIDO->value)
                ->whereBetween('start_date', [
                    $firstAppointment->start_date->copy()->addMinute(),
                    $cohortEndDate,
                ])
                ->orderBy('start_date')
                ->first();

            return (object) [
                'patient_id' => $firstAppointment->patient_id,
                'patient_name' => $patient->name,
                'first_appointment_date' => $firstAppointment->start_date,
                'retention_deadline' => $cohortEndDate,
                'retained' => $secondAppointment !== null,
                'second_appointment_date' => $secondAppointment?->start_date,
                'days_to_retention' => $secondAppointment ? $firstAppointment->start_date->diffInDays($secondAppointment->start_date) : null,
            ];
        });

        return $retentionData->values();
    }

    /**
     * Get absenteeism summary within a date range.
     * Groups ABSENT appointments by day of week, time slot, and reason.
     *
     * @return Collection Absenteeism records enriched with metadata
     */
    public function getAbsenteeismSummary(Carbon $startDate, Carbon $endDate): Collection
    {
        // Get all ABSENT appointments in the date range
        $appointments = Appointment::where('status', AppointmentStatus::AUSENTE->value)
            ->whereBetween('start_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with('patient')
            ->get();

        // Enrich with metadata for grouping
        return $appointments->map(function ($appointment) {
            $hour = $appointment->start_date->hour;
            $dayOfWeek = [
                0 => 'Domingo',
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
            ][$appointment->start_date->dayOfWeek];

            return (object) [
                'id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'patient_name' => $appointment->patient->name,
                'date' => $appointment->start_date->format('Y-m-d'),
                'time' => $appointment->start_date->format('H:i'),
                'day_of_week' => $dayOfWeek,
                'time_slot' => $this->getTimeSlot($hour),
                'reason' => $appointment->reason,
                'appointment_date' => $appointment->start_date,
            ];
        })->values();
    }

    /**
     * Map 24-hour format to readable time slot.
     */
    private function getTimeSlot(int $hour): string
    {
        return match (true) {
            $hour >= 6 && $hour < 12 => 'Mañana',
            $hour >= 12 && $hour < 18 => 'Tarde',
            $hour >= 18 && $hour < 24 => 'Noche',
            default => 'Madrugada',
        };
    }
}
