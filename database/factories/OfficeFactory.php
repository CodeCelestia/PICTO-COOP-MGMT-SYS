<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\Sdn;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'sdn_id'                  => Sdn::factory(),
            'name'                    => $this->faker->company() . ' Cooperative',
            'code'                    => strtoupper($this->faker->unique()->lexify('COOP-????')),
            'cooperative_type'        => $this->faker->randomElement(['primary', 'secondary', 'tertiary']),
            'registration_number'     => $this->faker->unique()->numerify('CDA-####-######'),
            'status'                  => 'active',
            'allow_self_registration' => false,
            'contact_email'           => $this->faker->email(),
            'contact_phone'           => $this->faker->phoneNumber(),
        ];
    }

    public function allowsSelfRegistration(): static
    {
        return $this->state(['allow_self_registration' => true]);
    }
}
