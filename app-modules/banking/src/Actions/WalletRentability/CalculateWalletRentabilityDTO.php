<?php

declare(strict_types=1);

namespace Platform\Banking\Actions\WalletRentability;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Carbon\CarbonInterface;

final class CalculateWalletRentabilityDTO
{
    public function __construct(
        public BigNumber|BigInteger|BigDecimal $balance,
        public BigNumber|BigInteger|BigDecimal $rate,
        public CarbonInterface $rentabilityMonth,
        public int $rentabilityPeriodInDays
    ) {}
}
