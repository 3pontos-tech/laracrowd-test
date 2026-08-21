<?php

namespace Platform\Placements\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Mail\PlacementStepFailedMail;
use Platform\Placements\Models\Placement;

abstract class AbstractPlacementStep
{
    public function __construct(
        public Placement $placement
    ) {}

    /**
     * Get the possible choices for the current step.
     *
     * @return array<string, string|null>
     */
    abstract public function choices(): array;

    /**
     * Move to the next step in the placement process.
     */
    abstract public function processStep(): void;

    abstract public function notify(): void;

    abstract public function canChange(): bool;

    /**
     * Handle the placement process step.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function (): void {
                $this->processStep();
                $this->notify();
            });
        } catch (\Throwable) {
            Mail::to(config('platform.ops_email'))->send(
                new PlacementStepFailedMail($this->placement)
            );
        }
    }

    /**
     * Cancel the placement process.
     */
    public function cancel(): void
    {
        $this->placement->update([
            'process' => PlacementProcess::Cancelled,
            'status' => PlacementStatus::Cancelled,
        ]);

        $this->placement->wallet()->delete();
    }
}
