<?php

namespace Platform\Placements\Enums\Contracts;

use App\Enums\Concerns\StringifyEnum;

enum ContractStatusEnum: string
{
    use StringifyEnum;

    case Unsent = 'unsent';
    case Pending = 'pending';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
