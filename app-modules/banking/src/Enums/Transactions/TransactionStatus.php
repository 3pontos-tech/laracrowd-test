<?php

namespace Platform\Banking\Enums\Transactions;

use App\Enums\Concerns\StringifyEnum;

enum TransactionStatus: string
{
    use StringifyEnum;

    case Pending = 'pending';
    case Completed = 'completed';

    case Failed = 'failed';

}
