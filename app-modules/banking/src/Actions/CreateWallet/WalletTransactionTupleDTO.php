<?php

declare(strict_types=1);

namespace Platform\Banking\Actions\CreateWallet;

class WalletTransactionTupleDTO
{
    public function __construct(
        public readonly ?WalletTransactionEntryDTO $from = null,
        public readonly ?WalletTransactionEntryDTO $to = null
    ) {}
}
