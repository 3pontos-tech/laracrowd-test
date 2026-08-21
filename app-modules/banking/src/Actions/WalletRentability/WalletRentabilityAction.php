<?php

namespace Platform\Banking\Actions\WalletRentability;

use App\ValueObjects\Money;
use Brick\Math\RoundingMode;

final class WalletRentabilityAction
{
    public function handle(CalculateWalletRentabilityDTO $dto): WalletRentabilityResponse
    {
        $scale = Money::getScale(); // default: 8

        $balance = $dto->balance; // TODO: BigDecimal or BigInteger?
        $rate = $dto->rate->dividedBy(100, $scale, RoundingMode::HALF_EVEN);

        $dailyRate = $rate->dividedBy(
            $dto->rentabilityMonth->daysInMonth,
            $scale,
            RoundingMode::HALF_EVEN
        );

        $calculatedRentability = $balance
            ->multipliedBy($dailyRate)
            ->multipliedBy($dto->rentabilityPeriodInDays)
            ->toScale($scale, RoundingMode::HALF_EVEN);

        // TODO: ask about rounding to 2 decimals for business logic
        $rentabilityRounded = $calculatedRentability->toScale(2, RoundingMode::HALF_EVEN);

        // New balance always uses the rounded rentability
        $newBalance = $balance->plus($rentabilityRounded)->toScale($scale, RoundingMode::HALF_EVEN);

        return new WalletRentabilityResponse(
            balance: $newBalance->toScale(8, RoundingMode::HALF_EVEN),
            rentabilityAmount: $rentabilityRounded->toScale(2, RoundingMode::HALF_EVEN)->toFloat(),
            rentabilityAmountInCurrency: $rentabilityRounded->toScale(8, RoundingMode::HALF_EVEN),
            rentabilityDailyRate: $dailyRate->toScale($scale)->toFloat(),
            rentabilityPeriodInDays: $dto->rentabilityPeriodInDays,
            rentabilityRelatedMonth: $dto->rentabilityMonth->format('Y-m'),
        );
    }
}
