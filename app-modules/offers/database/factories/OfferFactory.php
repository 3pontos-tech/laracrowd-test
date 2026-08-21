<?php

namespace Platform\Offers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Platform\Offers\Enums\OfferModalityEnum;
use Platform\Offers\Enums\OfferRiskEnum;
use Platform\Offers\Enums\OfferStatusEnum;
use Platform\Offers\Enums\WithdrawalsPeriodicityEnum;
use Platform\Offers\Models\Offer;
use Platform\Offers\Models\Startup;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $romanNumbers = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $offerName = $this->faker->word().' '.$romanNumbers[$this->faker->numberBetween(0, 11)];

        return [
            'startup_id' => Startup::factory(),
            'reference' => 'O'.$this->faker->unique()->numberBetween(1000, 9999),
            'slug' => str($offerName)->slug(),
            'status' => OfferStatusEnum::Active,
            'modality_type' => $this->faker->randomElement(OfferModalityEnum::cases()),
            'capturing_until' => $this->faker->date(),
            'finish_at' => $this->faker->date(),
            'name' => $offerName,
            'duration_in_months' => $this->faker->numberBetween(1, 24),
            'total_value' => $this->faker->numberBetween(1000_00000000, 100000_00000000),
            'total_shares_count' => $this->faker->numberBetween(1, 10),
            'risk_level' => OfferRiskEnum::Moderate,
            'roi' => $this->faker->randomNumber(2),
            'min_investment' => $this->faker->numberBetween(1_00000000, 100_00000000),
            'min_shares_count' => $this->faker->numberBetween(1001, 99999),
            'withdraw_percentage_limit' => $this->faker->numberBetween(1, 100),
            'withdraw_periodicity' => $this->faker->randomElement(WithdrawalsPeriodicityEnum::cases()),
            'withdraw_grace_period_in_months' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function crowdfunding(): OfferFactory
    {
        return $this->state(fn (array $attributes) => [
            'modality_type' => OfferModalityEnum::Crowdfunding,
        ]);
    }

    public function comercialPaper(): OfferFactory
    {
        return $this->state(fn (array $attributes) => [
            'modality_type' => OfferModalityEnum::CommercialPaper,
        ]);
    }

    public function withStatus(OfferStatusEnum $status): self
    {
        return $this->state(['status' => $status]);
    }

    public function withRisk(OfferRiskEnum $risk): self
    {
        return $this->state(['risk_level' => $risk]);
    }
}
