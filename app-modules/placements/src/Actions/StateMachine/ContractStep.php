<?php

namespace Platform\Placements\Actions\StateMachine;

use Illuminate\Support\Facades\Mail;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Mail\PlacementPaymentMail;

class ContractStep extends AbstractPlacementStep
{
    public function processStep(): void
    {
        $this->placement->update([
            'status' => PlacementStatus::Payment,
        ]);
    }

    public function choices(): array
    {
        return [
            PlacementStatus::Payment->value => PlacementStatus::Payment->value,
            PlacementStatus::Cancelled->value => PlacementStatus::Cancelled->value,
        ];
    }

    public function notify(): void
    {
        Mail::to($this->placement->user->email)->send(new PlacementPaymentMail($this->placement));
    }

    public function canChange(): bool
    {
        return true;
    }
}
