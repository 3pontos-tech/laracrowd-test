<?php

namespace Platform\Offers\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Platform\Offers\Models\Startup;

/**
 * @extends Factory<Startup>
 */
class StartupFactory extends Factory
{
    protected $model = Startup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'tax_id' => $this->faker->numerify('##.###.###/####-##'),
            'short_description' => $this->faker->text(120),
            'segment' => $this->faker->randomElement(['fintech', 'healthtech', 'agtech', 'retail', 'energy']),
        ];
    }
}
