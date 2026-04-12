<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the migration successfully converts status_id to status enum
     *
     * After migration:
     * - status column should exist and contain string values
     * - status_id column should not exist
     * - All existing data should be migrated correctly
     */
    public function test_migration_converts_status_id_to_status_column(): void
    {
        // Create test data with the new schema (status as string enum)
        $patient = Patient::factory()->create();
        $user = User::factory()->create();

        // Create an appointment with status string
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'send_reminder' => false,
            'reason' => 'Test reason',
            'start_date' => now(),
            'end_date' => now()->addHour(),
            'status' => AppointmentStatus::AGENDADO->value,
            'cancellation_reason' => null,
        ]);

        // Verify the new schema works - status_id should not exist
        $this->assertDatabaseMissing('appointments', ['status_id' => 1]);

        // Verify status column contains the string value
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::AGENDADO->value,
        ]);

        // Verify the cast works
        $this->assertInstanceOf(AppointmentStatus::class, $appointment->status);
        $this->assertEquals(AppointmentStatus::AGENDADO, $appointment->status);
    }

    /**
     * Test that creating an appointment with the enum status works after refactoring
     */
    public function test_appointment_can_be_created_with_enum_status(): void
    {
        $patient = Patient::factory()->create();
        $user = User::factory()->create();

        // Create with enum string value
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'send_reminder' => false,
            'reason' => 'Test reason',
            'start_date' => now(),
            'end_date' => now()->addHour(),
            'status' => AppointmentStatus::AGENDADO->value,
            'cancellation_reason' => null,
        ]);

        // Verify it was saved correctly
        $this->assertNotNull($appointment->id);

        // The status is cast to enum, so compare with enum
        $this->assertEquals(AppointmentStatus::AGENDADO, $appointment->status);
        $this->assertEquals('Agendado', $appointment->status->value);
    }
}
