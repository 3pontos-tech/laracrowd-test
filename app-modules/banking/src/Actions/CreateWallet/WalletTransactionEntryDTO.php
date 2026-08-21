<?php

declare(strict_types=1);

namespace Platform\Banking\Actions\CreateWallet;

use Platform\Banking\Enums\Transactions\TransactionEntryType;

final class WalletTransactionEntryDTO
{
    public function __construct(
        public string|int $walletId,
        public TransactionEntryType $entryType,
    ) {}
}
