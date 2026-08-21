<?php

use App\ValueObjects\Money;
use Platform\Banking\Actions\CreateWallet\CreateWalletTransaction;
use Platform\Banking\Actions\CreateWallet\NewWalletTransactionDTO;
use Platform\Banking\Actions\CreateWallet\WalletTransactionEntryDTO;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Banking\Models\Wallet;
use Platform\Placements\Models\Placement;

beforeEach(function (): void {
    config()->set('platform.money.scale', 8);

    $this->placement = Placement::factory()->create([
        'placement_starting_at' => now()->subMonths(2),
    ]);

    $this->wallet = Wallet::factory()->create([
        'ownable_type' => 'placements',
        'ownable_id' => $this->placement->getKey(),
        'status' => WalletStatus::Active,
        'balance' => 1_000_00000000,
        'total_invested' => 1_000_00000000,
    ]);

    $this->action = resolve(CreateWalletTransaction::class);
});

it('records a ledger with one entry per wallet', function (): void {
    $ledger = $this->action->newTransaction(new NewWalletTransactionDTO(
        entryType: TransactionEntryType::Credit,
        kind: TransactionKind::Earning,
        wallets: [new WalletTransactionEntryDTO($this->wallet->getKey(), TransactionEntryType::Credit)],
        amount: Money::from(50),
        description: 'Monthly earnings',
        status: TransactionStatus::Completed,
    ));

    expect($ledger->transactions()->count())->toBe(1);
});

it('credits the wallet balance when the transaction is completed', function (): void {
    $before = $this->wallet->balance->toFloat();

    $this->action->newTransaction(new NewWalletTransactionDTO(
        entryType: TransactionEntryType::Credit,
        kind: TransactionKind::Earning,
        wallets: [new WalletTransactionEntryDTO($this->wallet->getKey(), TransactionEntryType::Credit)],
        amount: Money::from(50),
        description: 'Monthly earnings',
        status: TransactionStatus::Completed,
    ));

    expect($this->wallet->refresh()->balance->toFloat())->toBe($before + 50.0);
});

it('leaves the balance untouched while the transaction is pending', function (): void {
    $before = $this->wallet->balance->toFloat();

    $this->action->newTransaction(new NewWalletTransactionDTO(
        entryType: TransactionEntryType::Debit,
        kind: TransactionKind::Earning,
        wallets: [new WalletTransactionEntryDTO($this->wallet->getKey(), TransactionEntryType::Debit)],
        amount: Money::from(50),
        description: 'Withdrawal request',
        status: TransactionStatus::Pending,
    ));

    expect($this->wallet->refresh()->balance->toFloat())->toBe($before);
});
