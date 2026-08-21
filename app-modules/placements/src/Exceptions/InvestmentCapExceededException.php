<?php

declare(strict_types=1);

namespace Platform\Placements\Exceptions;

use App\ValueObjects\Money;
use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

class InvestmentCapExceededException extends RuntimeException implements ShouldntReport
{
    public function __construct(
        public readonly Money $cap,
        public readonly Money $currentTotal,
        public readonly Money $attempted,
        public readonly Money $remaining,
    ) {
        parent::__construct(sprintf(
            'CVM annual cap exceeded: cap=%s, current=%s, attempted=%s, remaining=%s',
            number_format($cap->toFloat(), 2),
            number_format($currentTotal->toFloat(), 2),
            number_format($attempted->toFloat(), 2),
            number_format($remaining->toFloat(), 2),
        ));
    }
}
