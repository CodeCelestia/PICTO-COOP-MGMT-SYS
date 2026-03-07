<?php

namespace Database\Factories;

use App\Models\Sdn;
use Illuminate\Database\Eloquent\Factories\Factory;

class SdnFactory extends Factory
{
    protected $model = Sdn::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->company() . ' SDN',
            'description' => $this->faker->sentence(),
            'contact'     => $this->faker->email(),
            'created_by'  => null,
        ];
    }
}
