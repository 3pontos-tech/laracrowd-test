<?php

declare(strict_types=1);

namespace Platform\Banking\Actions\CreateWallet;

use App\ValueObjects\Money;
use Carbon\Carbon;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;

readonly class NewWalletTransactionDTO
{
    public function __construct(
        public TransactionEntryType $entryType,
        public TransactionKind $kind,
        public array $wallets,
        public Money $amount,
        public string $description = 'Transaction from wallet',
        public string $reference = 'Transaction from wallet',
        public ?Carbon $entryAt = null,
        public array $metadata = [],
        public TransactionStatus $status = TransactionStatus::Completed,
    ) {}
}
