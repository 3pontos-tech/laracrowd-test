<?php

namespace Platform\Placements\DTOs;

use Platform\Placements\Models\Contract;

readonly class ContractEventDTO
{
    public function __construct(
        public Contract $contract,
        public ?string $externalEventId = null,
    ) {}
}
