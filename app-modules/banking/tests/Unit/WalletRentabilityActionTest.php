<?php

use App\ValueObjects\Money;
use Platform\Banking\Actions\WalletRentability\CalculateWalletRentabilityDTO;
use Platform\Banking\Actions\WalletRentability\WalletRentabilityAction;

beforeEach(function (): void {
    config()->set('platform.money.scale', 8);
    $this->action = resolve(WalletRentabilityAction::class);
});

it('calculates a full month of earnings from the monthly rate', function (): void {
    $response = $this->action->handle(new CalculateWalletRentabilityDTO(
        balance: Money::from(10_000)->amount,
        rate: Money::from(1)->amount,
        rentabilityMonth: now()->startOfMonth(),
        rentabilityPeriodInDays: now()->daysInMonth,
    ));

    expect($response->rentabilityAmount)->toBe(100.0);
});

it('prorates earnings over a partial period', function (): void {
    $month = now()->startOfMonth();

    $response = $this->action->handle(new CalculateWalletRentabilityDTO(
        balance: Money::from(10_000)->amount,
        rate: Money::from(1)->amount,
        rentabilityMonth: $month,
        rentabilityPeriodInDays: (int) floor($month->daysInMonth / 2),
    ));

    expect($response->rentabilityAmount)->toBeLessThan(100.0)
        ->and($response->rentabilityAmount)->toBeGreaterThan(0.0);
});
