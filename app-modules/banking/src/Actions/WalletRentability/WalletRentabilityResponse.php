<?php

declare(strict_types=1);

namespace Platform\Banking\Actions\WalletRentability;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;

class WalletRentabilityResponse
{
    public function __construct(
        public BigNumber|BigInteger|BigDecimal $balance, // balance after rentability = balance + rentabilityAmount
        public float $rentabilityAmount, // rentability amount = balance * rentabilityPercentage / 100
        public BigNumber|BigInteger|BigDecimal $rentabilityAmountInCurrency, // rentability amount in currency = balance * rentabilityPercentage / 100
        public float $rentabilityDailyRate,
        public int $rentabilityPeriodInDays,
        public string $rentabilityRelatedMonth,
    ) {}
}
