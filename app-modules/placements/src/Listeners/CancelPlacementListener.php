<?php

namespace Platform\Placements\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Platform\Placements\Enums\Contracts\ContractTemplateTypeEnum;
use Platform\Placements\Events\ContractRejectedEvent;

class CancelPlacementListener implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function handle(ContractRejectedEvent $event): void
    {
        $contract = $event->dto->contract;
        $contract->markAsRejected();

        // Termination rejection: placement is already cancelled, skip re-cancellation
        if ($contract->template->template_type === ContractTemplateTypeEnum::Termination) {
            return;
        }

        $contract->placements()->first()->current_step->cancel();
    }
}
