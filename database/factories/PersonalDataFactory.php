<?php

namespace Database\Factories;

use App\Models\Gender;
use App\Models\PersonalData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PersonalData>
 */
class PersonalDataFactory extends Factory
{
    protected $model = PersonalData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'contact_id' => \App\Models\Contact::factory(),
            'address' => fake()->address(),
            'birth_date' => fake()->date(),
            'gender_id' => Gender::factory()->create(),
            'dni' => fake()->unique()->numerify('########'),
        ];
    }
}
