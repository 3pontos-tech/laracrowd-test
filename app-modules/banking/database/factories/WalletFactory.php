<?php

namespace Platform\Banking\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Platform\Banking\Enums\Wallet\WalletOwnerKind;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Banking\Models\Wallet;
use Platform\Offers\Models\Startup;
use Platform\Placements\Models\Placement;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'currency' => $this->faker->currencyCode(),
            'status' => WalletStatus::Active,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /** Alias documental: Active é o status liquidado canônico (o outro é EndOfLife). */
    public function settled(): self
    {
        return $this->state([
            'status' => WalletStatus::Active,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(['status' => WalletStatus::Inactive]);
    }

    public function blocked(): self
    {
        return $this->state(['status' => WalletStatus::Blocked]);
    }

    public function endOfLife(): self
    {
        return $this->state(['status' => WalletStatus::EndOfLife]);
    }

    public function forUser(?User $user = null): self
    {
        $user ??= User::factory()->create();

        return $this->state([
            'ownable_id' => $user->id,
            'ownable_type' => WalletOwnerKind::User,
        ]);
    }

    public function forStartup(?Startup $startup = null): self
    {
        $startup ??= Startup::factory()->create();

        return $this->state([
            'ownable_id' => $startup->id,
            'ownable_type' => WalletOwnerKind::Startup,
        ]);
    }

    public function forPlacement(?Placement $placement = null): self
    {
        $placement ??= Placement::factory()->create();

        return $this->state([
            'ownable_id' => $placement->getKey(),
            'ownable_type' => WalletOwnerKind::Placement,
        ]);
    }
}
