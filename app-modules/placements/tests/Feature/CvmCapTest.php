<?php

use App\Models\Finance\FinanceProfile;
use App\Models\Users\User;
use App\ValueObjects\Money;
use Platform\Offers\Enums\OfferModalityEnum;
use Platform\Offers\Models\Offer;
use Platform\Placements\Actions\Cvm\CalculateInvestorCvmCapAction;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Exceptions\InvestmentCapExceededException;
use Platform\Placements\Models\Placement;

beforeEach(function (): void {
    config()->set('platform.money.scale', 8);
    $this->action = resolve(CalculateInvestorCvmCapAction::class);
    $this->user = User::factory()->create();
    FinanceProfile::factory()->create(['user_id' => $this->user->getKey()]);
    $this->offer = Offer::factory()->create(['modality_type' => OfferModalityEnum::Crowdfunding]);
});

it('applies the flat annual cap to a standard investor', function (): void {
    expect($this->action->getCapForUser($this->user)->toFloat())
        ->toBe(CalculateInvestorCvmCapAction::DEFAULT_ANNUAL_CAP);
});

it('has no cap for a qualified investor', function (): void {
    $this->user->financeProfile->update(['is_qualified_investor' => true]);

    expect($this->action->getCapForUser($this->user->refresh()))->toBeNull();
});

it('counts placements of the current year toward the cap', function (): void {
    Placement::factory()->create([
        'user_id' => $this->user->getKey(),
        'offer_id' => $this->offer->getKey(),
        'status' => PlacementStatus::Active,
        'grand_total_amount' => 5_000_00000000,
        'placement_starting_at' => now(),
    ]);

    expect($this->action->getCurrentYearTotalForUser($this->user)->toFloat())->toBe(5_000.0);
});

it('rejects an amount that would exceed the cap', function (): void {
    Placement::factory()->create([
        'user_id' => $this->user->getKey(),
        'offer_id' => $this->offer->getKey(),
        'status' => PlacementStatus::Active,
        'grand_total_amount' => 18_000_00000000,
        'placement_starting_at' => now(),
    ]);

    $this->action->assertCanInvest($this->user, Money::from(5_000));
})->throws(InvestmentCapExceededException::class);
