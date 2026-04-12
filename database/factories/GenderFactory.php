<?php

namespace Database\Factories;

use App\Models\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gender>
 */
class GenderFactory extends Factory
{
    protected $model = Gender::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Masculino', 'Femenino', 'Otro']),
        ];
    }

    /**
     * Male gender.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Masculino',
        ]);
    }

    /**
     * Female gender.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Femenino',
        ]);
    }
}
