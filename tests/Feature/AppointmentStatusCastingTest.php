<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentStatusCastingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function appointment_can_be_created_with_enum_status(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::AGENDADO->value,
        ]);

        $this->assertNotNull($appointment->id);
        $this->assertInstanceOf(AppointmentStatus::class, $appointment->status);
        $this->assertEquals(AppointmentStatus::AGENDADO, $appointment->status);
    }

    #[Test]
    public function appointment_status_persists_and_casts_on_retrieval(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::ATENDIDO->value,
        ]);

        // Fetch from database
        $fetched = Appointment::find($appointment->id);

        $this->assertInstanceOf(AppointmentStatus::class, $fetched->status);
        $this->assertEquals(AppointmentStatus::ATENDIDO, $fetched->status);
    }

    #[Test]
    public function appointment_can_be_updated_with_new_status(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::AGENDADO->value,
        ]);

        $appointment->update(['status' => AppointmentStatus::CONFIRMADO->value]);

        $this->assertEquals(AppointmentStatus::CONFIRMADO, $appointment->status);

        $fetched = Appointment::find($appointment->id);
        $this->assertEquals(AppointmentStatus::CONFIRMADO, $fetched->status);
    }

    #[Test]
    public function appointment_status_in_fillable(): void
    {
        $this->assertContains('status', (new Appointment)->getFillable());
    }

    #[Test]
    public function appointment_no_longer_has_status_id_foreign_key(): void
    {
        $appointment = new Appointment;

        // The fillable should not contain status_id
        $this->assertNotContains('status_id', $appointment->getFillable());
    }
}
