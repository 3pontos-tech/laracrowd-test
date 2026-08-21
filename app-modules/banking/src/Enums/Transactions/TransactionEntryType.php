<?php

namespace Platform\Banking\Enums\Transactions;

use App\Enums\Concerns\StringifyEnum;

enum TransactionEntryType: string
{
    use StringifyEnum;

    case Credit = 'credit';

    case Debit = 'debit';

}
