<?php

namespace Platform\Banking\Withdrawals\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\Banking\Actions\CreateWallet\CreateWalletTransaction;
use Platform\Banking\Actions\CreateWallet\NewWalletTransactionDTO;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Models\Wallet;
use Platform\Banking\Withdrawals\Events\WithdrawalApprovedEvent;

class ApproveWithdrawalAction
{
    public function __construct(
        private readonly CreateWalletTransaction $createTransaction,
    ) {}

    public function handle(Ledger $ledger): void
    {
        throw_unless(
            $ledger->status === TransactionStatus::Pending,
            ValidationException::withMessages(['ledger' => __('banking::withdrawals.validation.ledger_not_pending')]),
        );

        DB::transaction(function () use ($ledger): void {
            $transaction = $ledger->transactions()->first();
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($transaction->wallet_id);

            throw_unless(
                $wallet->balance->toFloat() >= $transaction->amount->toFloat(),
                ValidationException::withMessages(['amount' => __('banking::withdrawals.validation.insufficient_balance')]),
            );

            $ledger->update(['status' => TransactionStatus::Completed]);

            $dto = new NewWalletTransactionDTO(
                entryType: TransactionEntryType::Debit,
                kind: $transaction->transaction_kind,
                wallets: [],
                amount: $transaction->amount,
                status: TransactionStatus::Completed,
            );

            $this->createTransaction->processWalletDebitTransaction($wallet, $dto);
        });

        event(new WithdrawalApprovedEvent($ledger));
    }
}
