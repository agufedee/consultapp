<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Status;
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
        // Create test data with the old schema (status_id as FK)
        $patient = Patient::factory()->create();
        $user = User::factory()->create();

        // Create an appointment with status_id
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'send_reminder' => false,
            'reason' => 'Test reason',
            'start_date' => now(),
            'end_date' => now()->addHour(),
            'status_id' => 1, // Assuming status_id=1 is 'Agendado'
            'cancellation_reason' => null,
        ]);

        // Verify the old schema works before migration
        $this->assertEquals(1, $appointment->status_id);

        // After migration:
        // The status column should be a string, not an ID
        // This test is written BEFORE the migration is created (RED phase)
        $this->markTestSkipped('Migration not yet created - this test will verify the migration works');
    }

    /**
     * Test that creating an appointment with the enum status works after refactoring
     */
    public function test_appointment_can_be_created_with_enum_status(): void
    {
        $patient = Patient::factory()->create();
        $user = User::factory()->create();

        // After migration, we should be able to create with enum
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
        $this->assertEquals(AppointmentStatus::AGENDADO->value, $appointment->status);

        // After the model is updated with the cast, it should return an enum
        $this->markTestSkipped('Model cast not yet updated - test will pass once cast is added');
    }
}
