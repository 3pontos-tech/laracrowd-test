<?php

declare(strict_types=1);

namespace Platform\Banking\Database\Factories\Transactions;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;
use Platform\Banking\Models\Transactions\Ledger;

/**
 * @extends Factory<Ledger>
 */
class LedgerFactory extends Factory
{
    protected $model = Ledger::class;

    /** Completed é fixo: é o único status em que a carteira se move, e sortear deixaria qualquer soma passando por acaso. */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(TransactionKind::cases()),
            'description' => $this->faker->text(),
            'reference' => $this->faker->word(),
            'status' => TransactionStatus::Completed,
            'metadata' => $this->faker->words(),
            'entry_at' => Date::now(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function completed(): self
    {
        return $this->state([
            'status' => TransactionStatus::Completed,
        ]);
    }

    public function pending(): self
    {
        return $this->state([
            'status' => TransactionStatus::Pending,
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status' => TransactionStatus::Failed,
        ]);
    }
}
