<?php

declare(strict_types=1);

namespace Platform\Placements\Scopes;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Platform\Offers\Enums\OfferModalityEnum;
use Platform\Placements\Enums\PlacementStatus;

trait PlacementScopes
{
    #[Scope]
    protected function listables(Builder $builder): void
    {
        $builder->whereNotIn('status', [
            PlacementStatus::Draft,
            PlacementStatus::Cancelled,
        ]);
    }

    #[Scope]
    protected function forUser(Builder $builder, string $userId): void
    {
        $builder->where('user_id', $userId);
    }

    #[Scope]
    protected function forCrowdfunding(Builder $builder): void
    {
        $builder->whereHas('offer', fn (Builder $q) => $q->where('modality_type', OfferModalityEnum::Crowdfunding));
    }

    #[Scope]
    protected function forSecuritization(Builder $builder): void
    {
        $builder->whereHas('offer', fn (Builder $q) => $q->where('modality_type', OfferModalityEnum::CommercialPaper));
    }

    #[Scope]
    protected function forCalendarYear(Builder $builder, int $year): void
    {
        $builder->whereYear('placement_starting_at', $year);
    }

    #[Scope]
    protected function countedTowardCvmCap(Builder $builder): void
    {
        $builder->whereIn('status', [
            PlacementStatus::Active,
            PlacementStatus::Finished,
        ]);
    }
}
