<?php

namespace Platform\Placements\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Platform\Placements\Enums\Contracts\ContractStatusEnum;
use Platform\Placements\Enums\Contracts\ContractTemplateTypeEnum;
use Platform\Placements\Events\ContractSignedEvent;

final class AdvancePlacementOnContractSignedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ContractSignedEvent $event): void
    {
        $contract = $event->dto->contract;
        $placement = $contract->placements()->first();
        if (! $placement && $contract->isContract()) {
            return;
        }

        if ($contract->status === ContractStatusEnum::Signed) {
            return;
        }

        $contract->markAsSigned();

        if ($contract->template->template_type === ContractTemplateTypeEnum::Contract) {
            $placement->current_step->handle();
        }
    }
}
