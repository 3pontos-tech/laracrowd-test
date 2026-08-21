<?php

namespace Platform\Placements\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Platform\Placements\Console\Commands\ProcessWithdrawalsCommand;
use Platform\Placements\Events\ContractRejectedEvent;
use Platform\Placements\Events\ContractSignedEvent;
use Platform\Placements\Listeners\AdvancePlacementOnContractSignedListener;
use Platform\Placements\Listeners\CancelPlacementListener;

class PlacementsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(ContractSignedEvent::class, AdvancePlacementOnContractSignedListener::class);
        Event::listen(ContractRejectedEvent::class, CancelPlacementListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessWithdrawalsCommand::class,
            ]);
        }
    }
}
