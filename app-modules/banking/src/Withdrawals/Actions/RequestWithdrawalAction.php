<?php

namespace Platform\Banking\Withdrawals\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platform\Banking\Actions\CreateWallet\CreateWalletTransaction;
use Platform\Banking\Actions\CreateWallet\NewWalletTransactionDTO;
use Platform\Banking\Actions\CreateWallet\WalletTransactionEntryDTO;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Withdrawals\DTOs\RequestWithdrawalDTO;
use Platform\Banking\Withdrawals\Events\WithdrawalRequestedEvent;
use Platform\Placements\Enums\PlacementStatus;

class RequestWithdrawalAction
{
    public function __construct(
        private readonly CreateWalletTransaction $createTransaction,
    ) {}

    public function handle(RequestWithdrawalDTO $dto): Ledger
    {
        $placement = $dto->placement;
        $wallet = $placement->wallet;
        $offer = $placement->offer;

        throw_unless(
            $placement->status === PlacementStatus::Active,
            ValidationException::withMessages(['placement' => __('banking::withdrawals.validation.placement_not_active')]),
        );

        throw_unless(
            $placement->canWithdrawForMonth(now()),
            ValidationException::withMessages(['placement' => __('banking::withdrawals.validation.withdraw_window_closed')]),
        );

        throw_unless(
            $dto->amount->toFloat() > 0,
            ValidationException::withMessages(['amount' => __('banking::withdrawals.validation.amount_must_be_positive')]),
        );

        throw_unless(
            $wallet !== null,
            ValidationException::withMessages(['wallet' => __('banking::withdrawals.validation.wallet_not_found')]),
        );

        if ($dto->kind === TransactionKind::Earning) {
            $maxAmount = $wallet->available_earnings->toFloat() * ($offer->withdraw_percentage_limit / 100);

            throw_unless(
                $dto->amount->toFloat() <= $maxAmount,
                ValidationException::withMessages(['amount' => __('banking::withdrawals.validation.earning_limit_exceeded')]),
            );
        }

        if ($dto->kind === TransactionKind::Investment) {
            throw_unless(
                $dto->amount->toFloat() <= $wallet->balance->toFloat(),
                ValidationException::withMessages(['amount' => __('banking::withdrawals.validation.insufficient_balance')]),
            );
        }

        $reference = sprintf(
            'withdrawal-%s-%s-%s-%s',
            $dto->kind->value,
            now()->format('Y-m'),
            $placement->slug,
            Str::random(8),
        );

        $ledger = $this->createTransaction->newTransaction(new NewWalletTransactionDTO(
            entryType: TransactionEntryType::Debit,
            kind: $dto->kind,
            wallets: [new WalletTransactionEntryDTO($wallet->getKey(), TransactionEntryType::Debit)],
            amount: $dto->amount,
            description: sprintf('Withdrawal request (%s)', $dto->kind->value),
            reference: $reference,
            metadata: [
                'placement_id' => $placement->getKey(),
                'offer_id' => $placement->offer_id,
                'kind' => $dto->kind->value,
                'requested_at' => now()->toIso8601String(),
            ],
            status: TransactionStatus::Pending,
        ));

        event(new WithdrawalRequestedEvent($ledger, $placement));

        return $ledger;
    }
}
