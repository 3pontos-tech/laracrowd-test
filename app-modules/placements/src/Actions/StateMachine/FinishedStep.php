<?php

namespace Platform\Placements\Actions\StateMachine;

use Illuminate\Support\Facades\Mail;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Mail\PlacementFinishedMail;

class FinishedStep extends AbstractPlacementStep
{
    public function processStep(): void {}

    public function choices(): array
    {
        return [];
    }

    public function notify(): void
    {
        Mail::to($this->placement->user->email)->send(new PlacementFinishedMail($this->placement));
    }

    public function canChange(): bool
    {
        return false;
    }
}
