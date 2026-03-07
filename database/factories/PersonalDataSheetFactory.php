<?php

namespace Database\Factories;

use App\Models\PersonalDataSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalDataSheetFactory extends Factory
{
    protected $model = PersonalDataSheet::class;

    public function definition(): array
    {
        return [
            'office_id'    => null,
            'first_name'   => $this->faker->firstName(),
            'middle_name'  => $this->faker->lastName(),
            'last_name'    => $this->faker->lastName(),
            'name_extension' => null,
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'place_of_birth' => $this->faker->city(),
            'gender'       => $this->faker->randomElement(['Male', 'Female']),
            'civil_status' => $this->faker->randomElement(['Single', 'Married', 'Widowed']),
            'citizenship'  => 'Filipino',
            'phone_number' => $this->faker->phoneNumber(),
            'email'        => $this->faker->unique()->safeEmail(),
        ];
    }
}
