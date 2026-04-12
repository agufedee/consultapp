<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $nutritionist;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $nutritionistRole = Role::create(['name' => 'Nutricionista']);
        $adminRole = Role::create(['name' => 'Admin']);

        // Create users
        $this->nutritionist = User::factory()->create([
            'role_id' => $nutritionistRole->id,
        ]);
    }

    /** @test */
    public function appointment_cannot_be_created_in_the_past(): void
    {
        $this->actingAs($this->nutritionist);

        $this->patient = Patient::factory()->create(['active' => true]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se pueden agendar turnos en el pasado.');

        Appointment::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->nutritionist->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->subDay()->addHour(),
            'status' => AppointmentStatus::AGENDADO->value,
        ]);
    }

    /** @test */
    public function appointment_cannot_be_outside_business_hours(): void
    {
        $this->actingAs($this->nutritionist);

        $this->patient = Patient::factory()->create(['active' => true]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Los turnos deben agendarse entre las 07:00 y las 21:00.');

        Appointment::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->nutritionist->id,
            'start_date' => now()->addDay()->setHour(22), // 10 PM
            'end_date' => now()->addDay()->setHour(23),
            'status' => AppointmentStatus::AGENDADO->value,
        ]);
    }

    /** @test */
    public function appointment_cannot_be_created_for_inactive_patient(): void
    {
        $this->actingAs($this->nutritionist);

        $this->patient = Patient::factory()->create(['active' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se pueden agendar turnos para pacientes inactivos.');

        Appointment::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->nutritionist->id,
            'start_date' => now()->addDay()->setHour(10),
            'end_date' => now()->addDay()->setHour(11),
            'status' => AppointmentStatus::AGENDADO->value,
        ]);
    }

    /** @test */
    public function overlapping_appointments_should_be_detected(): void
    {
        $this->patient = Patient::factory()->create(['active' => true]);

        // Create first appointment from 10:00 to 11:00 (tomorrow to avoid past)
        $tomorrow = now()->addDay()->setHour(10);
        $existingAppointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->nutritionist->id,
            'start_date' => $tomorrow,
            'end_date' => (clone $tomorrow)->modify('+1 hour'),
            'status' => AppointmentStatus::AGENDADO->value,
        ]);

        // Check overlap - appointment from 10:30 to 11:30 overlaps
        $this->assertTrue(
            $existingAppointment->overlapsWithUser($this->nutritionist->id)
        );

        // Non-overlapping appointment should not be detected (with itself excluded)
        $this->assertFalse(
            $existingAppointment->overlapsWithUser($this->nutritionist->id, $existingAppointment->id)
        );
    }

    /** @test */
    public function valid_appointment_can_be_created(): void
    {
        $this->actingAs($this->nutritionist);

        $this->patient = Patient::factory()->create(['active' => true]);

        $appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->nutritionist->id,
            'start_date' => now()->addDay()->setHour(10),
            'end_date' => now()->addDay()->setHour(11),
            'status' => AppointmentStatus::AGENDADO->value,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->nutritionist->id,
        ]);
    }
}
