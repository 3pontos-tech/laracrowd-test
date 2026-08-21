<?php

declare(strict_types=1);

namespace Platform\Banking\Withdrawals\DTOs;

use App\ValueObjects\Money;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Placements\Models\Placement;

readonly class RequestWithdrawalDTO
{
    public function __construct(
        public Placement $placement,
        public TransactionKind $kind,
        public Money $amount,
    ) {}
}
