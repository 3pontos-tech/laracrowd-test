<?php

namespace Platform\Placements\Actions\StateMachine;

use Illuminate\Support\Facades\Mail;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Mail\PlacementFinishedMail;

class ActiveStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        $this->placement->update([
            'status' => PlacementStatus::Finished,
        ]);

        $this->placement->wallet()->update([
            'status' => WalletStatus::EndOfLife,
        ]);
    }

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
