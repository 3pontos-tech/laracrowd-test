<?php

namespace Platform\Placements\Actions\StateMachine;

use Platform\Banking\Actions\CreateWallet\CreateWalletTransaction;
use Platform\Banking\Actions\CreateWallet\NewWalletTransactionDTO;
use Platform\Banking\Actions\CreateWallet\WalletTransactionEntryDTO;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Banking\Models\Wallet;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;

class DraftStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        $this->placement->update([
            'status' => PlacementStatus::Contract,
            'process' => PlacementProcess::Reserved,
        ]);

        $this->createDisabledWallet();
    }

    public function choices(): array
    {
        return [
            PlacementStatus::Contract->value => PlacementStatus::Contract->value,
            PlacementStatus::Cancelled->value => PlacementStatus::Cancelled->value,
        ];
    }

    public function notify(): void
    {
        //
    }

    public function createDisabledWallet(): void
    {
        /** @var Wallet $wallet */
        $wallet = $this->placement->wallet()->firstOrCreate([
            'status' => WalletStatus::Inactive,
            'currency' => 'BRL',
        ]);

        $entryType = TransactionEntryType::Credit;

        resolve(CreateWalletTransaction::class)->newTransaction(new NewWalletTransactionDTO(
            entryType: $entryType,
            kind: TransactionKind::Investment,
            wallets: [
                new WalletTransactionEntryDTO($wallet->getKey(), $entryType),
            ],
            amount: $this->placement->grand_total_amount,
            description: 'Wallet created for placement',
        ));
    }

    public function canChange(): bool
    {
        return true;
    }
}
