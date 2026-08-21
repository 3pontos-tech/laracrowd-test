<?php

namespace Platform\Placements\Actions\StateMachine;

use Illuminate\Support\Facades\Mail;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Mail\PlacementCancelledMail;

class CancelledStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        $this->placement->update([
            'status' => PlacementStatus::Cancelled,
            'process' => PlacementProcess::Cancelled,
        ]);

        $this->removeWallet();
    }

    public function choices(): array
    {
        return [];
    }

    public function notify(): void
    {
        Mail::to($this->placement->user->email)->send(new PlacementCancelledMail($this->placement));
    }

    public function removeWallet(): void
    {
        $this->placement->wallet()->delete();
    }

    public function canChange(): bool
    {
        return false;
    }
}
