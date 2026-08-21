<?php

declare(strict_types=1);

namespace App\Reporting;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Platform\Banking\Models\Wallet;
use Platform\Offers\Models\Offer;
use Platform\Placements\Enums\PlacementStatus;

/**
 * Queries behind the admin and investor screens.
 *
 * The panels are built on top of Eloquent tables that sort, filter and search
 * straight over the monetary columns, so every listing below reaches for
 * `wallets.balance` and `user_placements.grand_total_amount` as if they were
 * ordinary indexed columns.
 */
class PositionQueries
{
    /**
     * Wallet listing, sorted by balance — the default ordering of the admin table.
     */
    public function walletsByBalance(int $perPage = 25): LengthAwarePaginator
    {
        return Wallet::query()
            ->orderByDesc('balance')
            ->paginate($perPage);
    }

    /**
     * Wallets within a balance range — a filter exposed on the same table.
     */
    public function walletsInBalanceRange(int $min, int $max): Collection
    {
        return Wallet::query()
            ->whereBetween('balance', [$min, $max])
            ->orderBy('balance')
            ->get();
    }

    /**
     * Total raised per offer, straight from the placements table.
     */
    public function fundingByOffer(): Collection
    {
        return DB::table('user_placements')
            ->select('offer_id', DB::raw('SUM(grand_total_amount) AS funded'), DB::raw('COUNT(*) AS placements'))
            ->whereIn('status', [PlacementStatus::Active->value, PlacementStatus::Finished->value])
            ->groupBy('offer_id')
            ->orderByDesc('funded')
            ->get();
    }

    /**
     * The offer grid as the investor sees it.
     *
     * Note that `percentage_invested` and `total_invested_amount` are appended
     * attributes, so they are resolved for every offer the grid renders.
     */
    public function offerGrid(): Collection
    {
        return Offer::query()
            ->where('active', true)
            ->get();
    }

    /**
     * Consolidated position of a single investor.
     */
    public function investorPosition(string $userId): Collection
    {
        return DB::table('user_placements as p')
            ->leftJoin('wallets as w', function ($join): void {
                $join->on('w.ownable_id', '=', 'p.id')->where('w.ownable_type', '=', 'placements');
            })
            ->where('p.user_id', $userId)
            ->select([
                'p.slug',
                'p.status',
                'p.grand_total_amount',
                'w.balance',
                'w.total_earnings',
                'w.total_withdrawn',
            ])
            ->orderByDesc('p.placement_starting_at')
            ->get();
    }
}
