<?php

namespace Platform\Placements\Enums;

use App\Enums\Concerns\StringifyEnum;

enum PlacementProcess: string
{
    use StringifyEnum;

    case Draft = 'draft';
    case Reserved = 'reserved';

    case Approved = 'approved';

    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::Approved;
    }

    public function isReserved(): bool
    {
        return $this === self::Reserved;
    }
}
