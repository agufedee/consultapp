<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Business hours: 8am to 6pm, future dates only
        $hour = fake()->numberBetween(8, 17);
        $minute = fake()->randomElement([0, 15, 30, 45]);
        $daysAhead = fake()->numberBetween(1, 30);

        $startDate = now()
            ->addDays($daysAhead)
            ->setTime($hour, $minute);
        $endDate = (clone $startDate)->addHour();

        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'send_reminder' => fake()->boolean(),
            'reason' => fake()->sentence(3),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => AppointmentStatus::AGENDADO->value,
        ];
    }

    /**
     * Appointment for today during business hours.
     */
    public function today(): static
    {
        return $this->state(function (array $attributes) {
            $hour = fake()->numberBetween(8, 17);
            $minute = fake()->numberElement([0, 15, 30, 45]);
            $startDate = today()->setTime($hour, $minute);
            $endDate = (clone $startDate)->addHour();

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    /**
     * Completed appointment.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::ATENDIDO->value,
        ]);
    }

    /**
     * Cancelled appointment.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::CANCELADO->value,
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    /**
     * No-show appointment.
     */
    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::AUSENTE->value,
        ]);
    }
}
