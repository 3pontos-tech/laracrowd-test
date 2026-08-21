<?php

namespace Platform\Placements\Database\Factories;

use App\Models\Users\User;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Platform\Offers\Models\Offer;
use Platform\Placements\Enums\PlacementCompletedKind;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Models\Placement;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    protected $model = Placement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'offer_id' => Offer::factory(),
            'status' => PlacementStatus::Draft,
            'process' => PlacementProcess::Draft,
            'slug' => $this->faker->slug(),

            'grand_total_amount' => 10_00000000,

            'placement_payment_at' => $this->faker->dateTime(),
            'placement_starting_at' => $this->faker->dateTime(),
            'placement_finished_at' => $this->faker->dateTime(),
        ];
    }

    public function invested(Money|int|float $grandTotalAmount): self
    {
        return $this->state(fn (): array => [
            'grand_total_amount' => $grandTotalAmount,
        ]);
    }

    public function withStatus(PlacementStatus $status): self
    {
        return $this->state(fn (): array => [
            'status' => $status,
        ]);
    }

    public function withProcessStep(PlacementProcess $process): self
    {
        return $this->state(fn (): array => [
            'process' => $process,
        ]);
    }

    public function terminated(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Cancelled,
            'completion_reason' => PlacementCompletedKind::ContractBreached,
            'completion_notes' => $this->faker->sentence(),
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Draft,
            'process' => PlacementProcess::Draft,
        ]);
    }

    public function compliance(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Compliance,
            'process' => PlacementProcess::Reserved,
        ]);
    }

    public function contract(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Contract,
            'process' => PlacementProcess::Reserved,
        ]);
    }

    public function payment(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Payment,
            'process' => PlacementProcess::Approved,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Active,
            'process' => PlacementProcess::Approved,
        ]);
    }

    public function finished(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Finished,
            'process' => PlacementProcess::Cancelled,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementStatus::Cancelled,
            'process' => PlacementProcess::Cancelled,
            'completion_reason' => $this->faker->randomElement(PlacementCompletedKind::cases()),
            'completion_notes' => $this->faker->sentence(),
        ]);
    }
}
