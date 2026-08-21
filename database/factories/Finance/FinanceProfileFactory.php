<?php

namespace Database\Factories\Finance;

use App\Models\Finance\FinanceProfile;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceProfile>
 */
class FinanceProfileFactory extends Factory
{
    protected $model = FinanceProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'annual_gross_income' => $this->faker->randomFloat(2, 30_000, 180_000),
            'financial_investments_amount' => $this->faker->randomFloat(2, 0, 150_000),
            'is_qualified_investor' => false,
        ];
    }

    public function highIncome(): self
    {
        return $this->state(fn (): array => [
            'annual_gross_income' => $this->faker->randomFloat(2, 250_000, 900_000),
        ]);
    }

    public function qualified(): self
    {
        return $this->state(fn (): array => ['is_qualified_investor' => true]);
    }
}
