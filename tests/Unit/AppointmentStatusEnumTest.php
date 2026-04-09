<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AppointmentStatus enum
 *
 * These are pure unit tests that verify the enum logic without database
 */
class AppointmentStatusEnumTest extends TestCase
{
    /**
     * Test that AppointmentStatus enum values match expected strings
     */
    public function test_appointment_status_enum_has_correct_values(): void
    {
        $this->assertEquals('Agendado', AppointmentStatus::AGENDADO->value);
        $this->assertEquals('Confirmado', AppointmentStatus::CONFIRMADO->value);
        $this->assertEquals('Atendido', AppointmentStatus::ATENDIDO->value);
        $this->assertEquals('Cancelado', AppointmentStatus::CANCELADO->value);
        $this->assertEquals('Ausente', AppointmentStatus::AUSENTE->value);
    }

    /**
     * Test that AppointmentStatus enum has all expected cases
     */
    public function test_appointment_status_enum_has_all_cases(): void
    {
        $cases = AppointmentStatus::cases();
        $this->assertCount(5, $cases);

        $caseNames = array_map(fn ($case) => $case->name, $cases);
        $this->assertContains('AGENDADO', $caseNames);
        $this->assertContains('CONFIRMADO', $caseNames);
        $this->assertContains('ATENDIDO', $caseNames);
        $this->assertContains('CANCELADO', $caseNames);
        $this->assertContains('AUSENTE', $caseNames);
    }

    /**
     * Test that AppointmentStatus enum getColor method returns correct colors
     */
    public function test_appointment_status_get_color_returns_correct_values(): void
    {
        $this->assertEquals('gray', AppointmentStatus::AGENDADO->getColor());
        $this->assertEquals('info', AppointmentStatus::CONFIRMADO->getColor());
        $this->assertEquals('success', AppointmentStatus::ATENDIDO->getColor());
        $this->assertEquals('danger', AppointmentStatus::CANCELADO->getColor());
        $this->assertEquals('warning', AppointmentStatus::AUSENTE->getColor());
    }

    /**
     * Test that AppointmentStatus enum getLabel method returns the value
     */
    public function test_appointment_status_get_label_returns_value(): void
    {
        $this->assertEquals('Agendado', AppointmentStatus::AGENDADO->getLabel());
        $this->assertEquals('Confirmado', AppointmentStatus::CONFIRMADO->getLabel());
        $this->assertEquals('Atendido', AppointmentStatus::ATENDIDO->getLabel());
    }

    /**
     * Test that AppointmentStatus::fromName resolves strings to enums
     */
    public function test_appointment_status_from_name_resolves_correctly(): void
    {
        $this->assertEquals(AppointmentStatus::AGENDADO, AppointmentStatus::fromName('Agendado'));
        $this->assertEquals(AppointmentStatus::CONFIRMADO, AppointmentStatus::fromName('Confirmado'));
        $this->assertEquals(AppointmentStatus::ATENDIDO, AppointmentStatus::fromName('Atendido'));
        $this->assertNull(AppointmentStatus::fromName('InvalidStatus'));
    }

    /**
     * Test that AppointmentStatus::toArray returns proper key-value pairs
     */
    public function test_appointment_status_to_array_returns_proper_format(): void
    {
        $array = AppointmentStatus::toArray();

        // Should have 5 entries
        $this->assertCount(5, $array);

        // Keys and values should be the status values
        $this->assertArrayHasKey('Agendado', $array);
        $this->assertEquals('Agendado', $array['Agendado']);
    }
}
