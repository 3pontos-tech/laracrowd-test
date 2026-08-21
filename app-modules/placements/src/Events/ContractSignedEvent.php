<?php

namespace Platform\Placements\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Platform\Placements\DTOs\ContractEventDTO;

class ContractSignedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ContractEventDTO $dto,
    ) {}
}
