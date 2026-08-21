<?php

namespace Platform\Banking\Actions\WalletRentability;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Platform\Offers\Models\Offer;
use Platform\Offers\Models\Rate;

readonly class GenerateOfferMonthlyRentabilityAction
{
    public function __construct(private PlacementMonthlyRentabilityAction $rentabilityAction) {}

    /**
     * @throws Exception
     */
    public function handle(Offer $offer, string $period, bool $dryRun = true): Collection
    {
        $targetMonth = Date::createFromFormat('Y-m', $period)->startOfMonth();

        $rate = $this->findValidRateForMonth($offer, $targetMonth);

        $placements = $this->getEligiblePlacements($offer);
        $reports = collect();

        foreach ($placements as $placement) {
            $report = $this->rentabilityAction->handle($placement, $rate, $targetMonth);

            $reports->push($report);
        }

        return $reports;
    }

    private function findValidRateForMonth(Offer $offer, Carbon $targetMonth): ?Rate
    {
        /** @var Rate */
        return $offer->rates()
            ->whereYear('starting_at', $targetMonth->year)
            ->whereMonth('starting_at', $targetMonth->month)
            ->latest('starting_at')
            ->first();
    }

    private function getEligiblePlacements(Offer $offer): Collection
    {
        return $offer->placements()
            ->with(['wallet', 'user'])
            ->whereHas('wallet')
            ->get();
    }
}
