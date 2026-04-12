<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PersonalData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personal_data_id' => PersonalData::factory(),
            'active' => true,
        ];
    }

    /**
     * Create a patient with a specific full name.
     * Usage: Patient::factory()->withName('Juan Pérez')->create()
     */
    public function withName(string $name): static
    {
        return $this->afterCreating(function (Patient $patient) use ($name) {
            $parts = explode(' ', $name, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
            $patient->personalData->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
        });
    }

    /**
     * Indicate that the patient is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the patient is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
