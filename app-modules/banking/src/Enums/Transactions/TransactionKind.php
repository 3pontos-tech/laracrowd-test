<?php

namespace Platform\Banking\Enums\Transactions;

use App\Enums\Concerns\StringifyEnum;

enum TransactionKind: string
{
    use StringifyEnum;

    case Investment = 'investment';

    case Earning = 'earning';

    case Refund = 'refund';

}
