<?php

declare(strict_types=1);

namespace Platform\Offers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Platform\Offers\Models\Offer;
use Platform\Offers\Models\Rate;

class RateFactory extends Factory
{
    protected $model = Rate::class;

    public function definition(): array
    {
        return [
            'startup_offer_id' => Offer::factory(),
            'initial_bond_value' => $this->faker->randomFloat(8, 1, 2),
            'current_bond_value' => $this->faker->randomFloat(8, 1, 2),
            'residual_bond_value' => $this->faker->randomFloat(8, 1, 2),
            'can_pay' => $this->faker->boolean(),

            'value' => $this->faker->randomFloat(8, 1, 2),
            'starting_at' => Date::now(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
