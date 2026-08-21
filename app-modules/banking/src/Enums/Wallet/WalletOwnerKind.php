<?php

namespace Platform\Banking\Enums\Wallet;

use App\Enums\Concerns\StringifyEnum;

enum WalletOwnerKind: string
{
    use StringifyEnum;

    case User = 'users';
    case Startup = 'startups';
    case Placement = 'placements';

}
