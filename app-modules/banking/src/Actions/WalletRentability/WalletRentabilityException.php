<?php

namespace Platform\Banking\Actions\WalletRentability;

use Exception;
use Platform\Banking\Enums\Wallet\MonthlyEarningsReport\SkipReason;
use Throwable;

class WalletRentabilityException extends Exception
{
    public function __construct(string $message, int $code, ?Throwable $previous, private readonly SkipReason $reason)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function of(SkipReason $reason): self
    {
        return new self($reason->getLabel(), 422, null, $reason);
    }

    public function getReason(): SkipReason
    {
        return $this->reason;
    }
}
