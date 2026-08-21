<?php

declare(strict_types=1);

namespace Platform\Banking\Database\Factories\Transactions;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Models\Transactions\Ledger;
use Platform\Banking\Models\Transactions\Transaction;
use Platform\Banking\Models\Wallet;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'ledger_id' => Ledger::factory(),
            'wallet_id' => Wallet::factory(),
            'entry_type' => TransactionEntryType::Credit,
            'transaction_kind' => TransactionKind::Investment,
            'amount' => $this->faker->randomNumber(),
            'currency' => $this->faker->currencyCode(),
            'description' => $this->faker->text(),
            'reference' => $this->faker->word(),
            'metadata' => null,
            'entry_at' => Date::now(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function credit(): self
    {
        return $this->state([
            'entry_type' => TransactionEntryType::Credit,
        ]);
    }

    public function debit(): self
    {
        return $this->state([
            'entry_type' => TransactionEntryType::Debit,
        ]);
    }

    public function earning(): self
    {
        return $this->state(['transaction_kind' => TransactionKind::Earning]);
    }

    public function investment(): self
    {
        return $this->state(['transaction_kind' => TransactionKind::Investment]);
    }

    public function refund(): self
    {
        return $this->state(['transaction_kind' => TransactionKind::Refund]);
    }

    public function forWallet(Wallet $wallet): self
    {
        return $this->state(['wallet_id' => $wallet->getKey()]);
    }

    /** Reaproveita um ledger já criado — o default cria um por transação. */
    public function onLedger(Ledger $ledger): self
    {
        return $this->state(['ledger_id' => $ledger->getKey()]);
    }
}
