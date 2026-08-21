<?php

namespace Platform\Banking\Actions\CreateWallet;

use Illuminate\Support\Facades\DB;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Models\Wallet;

class CreateWalletTransaction
{
    /**
     * @throws \Throwable
     */
    public function newTransaction(NewWalletTransactionDTO $transactionDTO): Ledger
    {
        return DB::transaction(function () use ($transactionDTO): Ledger {

            $ledger = Ledger::query()->create([
                'type' => $transactionDTO->kind,
                'description' => $transactionDTO->description,
                'reference' => $transactionDTO->reference,
                'status' => $transactionDTO->status,
                'metadata' => $transactionDTO->metadata,
                'entry_at' => now(),
            ]);

            foreach ($transactionDTO->wallets as $wallet) {
                $this->createTransaction(
                    $wallet->walletId,
                    $ledger,
                    $wallet->entryType,
                    $transactionDTO
                );
            }

            return $ledger;
        });
    }

    public function createTransaction(
        mixed $walletId,
        Ledger $ledger,
        TransactionEntryType $entryType,
        NewWalletTransactionDTO $transactionDTO
    ): void {
        $wallet = Wallet::query()->find($walletId);
        $wallet->transactions()->create([
            'ledger_id' => $ledger->getKey(),
            'entry_type' => $entryType,
            'transaction_kind' => $transactionDTO->kind,
            'amount' => $transactionDTO->amount,
            'currency' => config('platform.money.defaultCurrency'),
            'description' => $transactionDTO->description,
            'reference' => $transactionDTO->reference,
            'metadata' => $transactionDTO->metadata,
            'entry_at' => now(),
        ]);

        if ($transactionDTO->status === TransactionStatus::Completed) {
            match ($entryType) {
                TransactionEntryType::Credit => $this->processWalletCreditTransaction($wallet, $transactionDTO),
                TransactionEntryType::Debit => $this->processWalletDebitTransaction($wallet, $transactionDTO),
            };
        }
    }

    public function processWalletDebitTransaction(Wallet $wallet, NewWalletTransactionDTO $transactionDTO): void
    {
        $wallet->refresh();
        $amount = $transactionDTO->amount->toDatabase();

        $updates = [
            'balance' => $wallet->balance->amount->getUnscaledValue()->minus($amount),
            'total_withdrawn' => $wallet->total_withdrawn->amount->getUnscaledValue()->plus($amount),
        ];

        if ($transactionDTO->kind === TransactionKind::Earning) {
            $updates['available_earnings'] = $wallet->available_earnings->amount->getUnscaledValue()->minus($amount);
        }

        if (in_array($transactionDTO->kind, [TransactionKind::Investment, TransactionKind::Refund], true)) {
            $updates['total_invested'] = $wallet->total_invested->amount->getUnscaledValue()->minus($amount);
        }

        $wallet->update($updates);
    }

    private function processWalletCreditTransaction(Wallet $wallet, NewWalletTransactionDTO $transactionDTO): void
    {
        $builder = $wallet->refresh();

        $newBalance = $wallet->balance->amount->getUnscaledValue()->plus($transactionDTO->amount->toDatabase());

        $builder->update([
            'balance' => $newBalance,
        ]);

        $placement = $wallet->ownable;

        if ($transactionDTO->kind == TransactionKind::Earning) {
            $builder->update([
                'balance' => $newBalance,
                'total_earnings' => $wallet->total_earnings->amount->getUnscaledValue()->plus($transactionDTO->amount->toDatabase()),
                'available_earnings' => $wallet->available_earnings->amount->getUnscaledValue()->plus($transactionDTO->amount->toDatabase()),
            ]);

            if ($placement->placement_starting_at->addMonths($placement->offer->withdraw_grace_period_in_months)->isPast()) {
                $placement->update([
                    'automatic_withdrawal' => true,
                ]);
            }
        }

        if ($transactionDTO->kind == TransactionKind::Investment) {
            $builder->update([
                'balance' => $newBalance,
                'total_invested' => $wallet->total_invested->amount->getUnscaledValue()->plus($transactionDTO->amount->toDatabase()),
            ]);
        }
    }
}
