<?php

namespace Platform\Placements\Actions\StateMachine;

use Illuminate\Support\Facades\Mail;
use Platform\Banking\Actions\CreateWallet\CreateWalletTransaction;
use Platform\Banking\Actions\CreateWallet\NewWalletTransactionDTO;
use Platform\Banking\Actions\CreateWallet\WalletTransactionEntryDTO;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Mail\PlacementWithdrawalCompletedMail;

class WithdrawingStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        $this->placement->update([
            'status' => PlacementStatus::WithdrawalCompleted,
        ]);

        $this->registerRefundTransaction();
    }

    public function choices(): array
    {
        return [
            PlacementStatus::WithdrawalCompleted->value => PlacementStatus::WithdrawalCompleted->value,
        ];
    }

    public function notify(): void
    {
        Mail::to($this->placement->user->email)->send(
            new PlacementWithdrawalCompletedMail($this->placement)
        );
    }

    public function canChange(): bool
    {
        return true;
    }

    private function registerRefundTransaction(): void
    {
        $wallet = $this->placement->wallet;

        if (! $wallet) {
            return;
        }

        resolve(CreateWalletTransaction::class)->newTransaction(new NewWalletTransactionDTO(
            entryType: TransactionEntryType::Debit,
            kind: TransactionKind::Refund,
            wallets: [new WalletTransactionEntryDTO($wallet->getKey(), TransactionEntryType::Debit)],
            amount: $this->placement->grand_total_amount,
            description: sprintf('Estorno por desistência - %s', $this->placement->slug),
            reference: sprintf('refund-%s-%s', now()->format('Y-m'), $this->placement->slug),
            metadata: [
                'placement_id' => $this->placement->getKey(),
                'offer_id' => $this->placement->offer_id,
                'completion_reason' => $this->placement->completion_reason?->value,
            ],
        ));
    }
}
