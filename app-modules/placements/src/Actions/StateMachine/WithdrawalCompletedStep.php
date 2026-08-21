<?php

namespace Platform\Placements\Actions\StateMachine;

use Platform\Placements\Actions\AbstractPlacementStep;

class WithdrawalCompletedStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        //
    }

    public function choices(): array
    {
        return [];
    }

    public function notify(): void
    {
        //
    }

    public function canChange(): bool
    {
        return false;
    }
}
