<?php

declare(strict_types=1);

namespace Platform\Banking\Scopes;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Platform\Banking\Enums\Transactions\TransactionStatus;

trait TransactionScopes
{
    /** Só lançamento de ledger Completed moveu dinheiro: um saque recusado deixa a linha de débito gravada sem nunca ter debitado o saldo. */
    #[Scope]
    protected function settled(Builder $builder): void
    {
        $builder->whereHas('ledger', fn (Builder $query): Builder => $query->where('status', TransactionStatus::Completed));
    }

    /**
     * @param  list<string>  $walletIds
     */
    #[Scope]
    protected function forWallets(Builder $builder, array $walletIds): void
    {
        $builder->whereIn('wallet_id', $walletIds);
    }
}
