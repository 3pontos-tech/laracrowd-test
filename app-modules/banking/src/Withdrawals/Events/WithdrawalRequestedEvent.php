<?php

declare(strict_types=1);

namespace Platform\Banking\Withdrawals\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Placements\Models\Placement;

class WithdrawalRequestedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Ledger $ledger,
        public readonly Placement $placement,
    ) {}
}
