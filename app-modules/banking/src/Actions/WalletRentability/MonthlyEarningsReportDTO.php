<?php

declare(strict_types=1);

namespace Platform\Banking\Actions\WalletRentability;

use App\ValueObjects\Money;
use JsonSerializable;
use Platform\Banking\Enums\Wallet\MonthlyEarningsReport\ReportStatus;
use Platform\Banking\Enums\Wallet\MonthlyEarningsReport\SkipReason;

class MonthlyEarningsReportDTO implements JsonSerializable
{
    public function __construct(
        public string $placementSlug,
        public string $userName,
        public Money $walletBalanceBefore,
        public Money $walletBalanceAfter,
        public float $earningAmount,
        public ReportStatus $status,
        public ?SkipReason $skipReason = null,
        public int $rentabilityDays = 0,
        public float $withdrawalAmount = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'placement_slug' => $this->placementSlug,
            'username' => $this->userName,
            'wallet_balance_before' => $this->walletBalanceBefore->toFloat(),
            'wallet_balance_after' => $this->walletBalanceAfter->toFloat(),
            'earning_amount' => $this->earningAmount,
            'withdrawal_amount' => $this->withdrawalAmount,
            'status' => $this->status->value,
            'skip_reason' => $this->skipReason?->value,
            'rentability_days' => $this->rentabilityDays,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
