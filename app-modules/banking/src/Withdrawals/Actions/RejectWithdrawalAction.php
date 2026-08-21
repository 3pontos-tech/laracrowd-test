<?php

namespace Platform\Banking\Withdrawals\Actions;

use Illuminate\Validation\ValidationException;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Withdrawals\Events\WithdrawalRejectedEvent;

class RejectWithdrawalAction
{
    public function handle(Ledger $ledger, string $reason): void
    {
        throw_unless(
            $ledger->status === TransactionStatus::Pending,
            ValidationException::withMessages(['ledger' => __('banking::withdrawals.validation.ledger_not_pending')]),
        );

        $metadata = $ledger->metadata ?? [];
        $metadata['rejection_reason'] = $reason;

        $ledger->update([
            'status' => TransactionStatus::Failed,
            'metadata' => $metadata,
        ]);

        event(new WithdrawalRejectedEvent($ledger, $reason));
    }
}
