<?php

namespace Tests\Integration;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\ReportService;
use Tests\TestCase;

class ReportServiceIntegrationTest extends TestCase
{
    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService;
    }

    /**
     * Test: New Patients finds patients with first ATTENDED appointment in range
     */
    public function test_get_new_patients_finds_patients_with_first_attended_in_range(): void
    {
        $user = User::factory()->create();
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Patient 1: First ATTENDED in range
        Appointment::factory()->create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(5),
        ]);

        // Patient 2: First ATTENDED in range
        Appointment::factory()->create([
            'patient_id' => $patient2->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(10),
        ]);

        // Patient 3: ATTENDED but outside range (should not be counted)
        $patient3 = Patient::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $patient3->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $end->copy()->addDays(5),
        ]);

        $result = $this->reportService->getNewPatients($start, $end);

        $this->assertCount(2, $result);
        $this->assertTrue($result->pluck('patient_id')->contains($patient1->id));
        $this->assertTrue($result->pluck('patient_id')->contains($patient2->id));
        $this->assertFalse($result->pluck('patient_id')->contains($patient3->id));
    }

    /**
     * Test: New Patients excludes non-first appointments
     */
    public function test_get_new_patients_excludes_non_first_appointments(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Patient has two ATTENDED appointments in range
        // Should only be counted once (first one)
        $first = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(2),
        ]);

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(15),
        ]);

        $result = $this->reportService->getNewPatients($start, $end);

        $this->assertCount(1, $result);
        $this->assertEquals($first->id, $result->first()->id);
    }

    /**
     * Test: New Patients only counts ATENDIDO status
     */
    public function test_get_new_patients_only_counts_atendido_status(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Create appointments with various statuses
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AGENDADO->value,
            'start_date' => $start->copy()->addDays(5),
        ]);

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::CANCELADO->value,
            'start_date' => $start->copy()->addDays(10),
        ]);

        $result = $this->reportService->getNewPatients($start, $end);

        $this->assertCount(0, $result);
    }

    /**
     * Test: Patient Retention identifies retained patients
     */
    public function test_get_patient_retention_identifies_retained_patients(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Patient's first ATTENDED appointment
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(5),
        ]);

        // Patient's second ATTENDED appointment within 30 days
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(20),
        ]);

        $result = $this->reportService->getPatientRetention($start, $end, 30);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->retained);
    }

    /**
     * Test: Patient Retention respects retention window
     */
    public function test_get_patient_retention_respects_retention_window(): void
    {
        $user = User::factory()->create();
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Patient 1: Second appointment within 20-day window
        Appointment::factory()->create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(5),
        ]);
        Appointment::factory()->create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(15),
        ]);

        // Patient 2: Second appointment outside 20-day window
        Appointment::factory()->create([
            'patient_id' => $patient2->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(5),
        ]);
        Appointment::factory()->create([
            'patient_id' => $patient2->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(30),
        ]);

        $result = $this->reportService->getPatientRetention($start, $end, 20);

        $this->assertCount(2, $result);
        $retained = $result->firstWhere('patient_id', $patient1->id);
        $notRetained = $result->firstWhere('patient_id', $patient2->id);

        $this->assertTrue($retained->retained);
        $this->assertFalse($notRetained->retained);
    }

    /**
     * Test: Patient Retention only includes cohort in range
     */
    public function test_get_patient_retention_only_includes_cohort_in_range(): void
    {
        $user = User::factory()->create();
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        // Patient 1: First ATTENDED in range
        Appointment::factory()->create([
            'patient_id' => $patient1->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(5),
        ]);

        // Patient 2: First ATTENDED OUTSIDE range (should not be in cohort)
        Appointment::factory()->create([
            'patient_id' => $patient2->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $end->copy()->addDays(5),
        ]);

        $result = $this->reportService->getPatientRetention($start, $end);

        $this->assertCount(1, $result);
        $this->assertEquals($patient1->id, $result->first()->patient_id);
    }

    /**
     * Test: Absenteeism Summary counts AUSENTE appointments
     */
    public function test_get_absenteeism_summary_counts_ausente_appointments(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $start->copy()->addDays(5)->setHour(10),
            'reason' => 'Consulta General',
        ]);

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $start->copy()->addDays(10)->setHour(14),
            'reason' => 'Consulta General',
        ]);

        $result = $this->reportService->getAbsenteeismSummary($start, $end);

        $this->assertCount(2, $result);
        $this->assertTrue($result->pluck('patient_id')->contains($patient->id));
    }

    /**
     * Test: Absenteeism Summary excludes appointments outside range
     */
    public function test_get_absenteeism_summary_excludes_appointments_outside_range(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $start->copy()->addDays(5),
            'reason' => 'Consulta General',
        ]);

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $end->copy()->addDays(5),
            'reason' => 'Consulta General',
        ]);

        $result = $this->reportService->getAbsenteeismSummary($start, $end);

        $this->assertCount(1, $result);
    }

    /**
     * Test: Absenteeism Summary only counts AUSENTE
     */
    public function test_get_absenteeism_summary_only_counts_ausente(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::ATENDIDO->value,
            'start_date' => $start->copy()->addDays(5),
            'reason' => 'Consulta General',
        ]);

        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::CANCELADO->value,
            'start_date' => $start->copy()->addDays(10),
            'reason' => 'Consulta General',
        ]);

        $result = $this->reportService->getAbsenteeismSummary($start, $end);

        $this->assertCount(0, $result);
    }

    /**
     * Test: Absenteeism Summary groups by day and time
     */
    public function test_get_absenteeism_summary_groups_by_day_and_time(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $now = now();
        $start = $now->copy()->startOfMonth();

        // Morning absence (6-12)
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $start->copy()->setHour(10),
            'reason' => 'Consulta General',
        ]);

        // Afternoon absence (12-18)
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $start->copy()->addDays(1)->setHour(15),
            'reason' => 'Consulta General',
        ]);

        // Night absence (18-24)
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'status' => AppointmentStatus::AUSENTE->value,
            'start_date' => $start->copy()->addDays(2)->setHour(20),
            'reason' => 'Consulta General',
        ]);

        $result = $this->reportService->getAbsenteeismSummary($start, $start->copy()->addDays(3));

        $this->assertCount(3, $result);

        $morning = $result->firstWhere('time_slot', 'Mañana');
        $afternoon = $result->firstWhere('time_slot', 'Tarde');
        $night = $result->firstWhere('time_slot', 'Noche');

        $this->assertNotNull($morning);
        $this->assertNotNull($afternoon);
        $this->assertNotNull($night);
    }
}
