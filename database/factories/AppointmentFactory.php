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
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $endDate = (clone $startDate)->modify('+1 hour');

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
     * Appointment for today.
     */
    public function today(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('today 08:00', 'today 18:00');
            $endDate = (clone $startDate)->modify('+1 hour');

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
