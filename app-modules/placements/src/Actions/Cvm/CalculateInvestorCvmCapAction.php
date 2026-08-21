<?php

declare(strict_types=1);

namespace Platform\Placements\Actions\Cvm;

use App\Models\Users\User;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Platform\Placements\Enums\CvmInvestorCategory;
use Platform\Placements\Exceptions\InvestmentCapExceededException;
use Platform\Placements\Models\Placement;

class CalculateInvestorCvmCapAction
{
    public const DEFAULT_ANNUAL_CAP = 20_000.00;

    public const HIGH_INCOME_PERCENTAGE = 0.10;

    public function getCapForUser(User $user, ?int $year = null): ?Money
    {
        $profile = $user->financeProfile;

        if ($profile === null) {
            return Money::from(self::DEFAULT_ANNUAL_CAP);
        }

        $category = $profile->cvmCategory();

        if ($category->isUnlimited()) {
            return null;
        }

        if ($category === CvmInvestorCategory::HighIncome) {
            $income = (float) ($profile->annual_gross_income ?? 0);
            $investments = (float) ($profile->financial_investments_amount ?? 0);

            return Money::from(max($income, $investments) * self::HIGH_INCOME_PERCENTAGE);
        }

        return Money::from(self::DEFAULT_ANNUAL_CAP);
    }

    public function getCurrentYearTotalForUser(User $user, ?int $year = null): Money
    {
        $year ??= Date::now()->year;

        $totalDb = Placement::query()
            ->forUser($user->id)
            ->forCrowdfunding()
            ->forCalendarYear($year)
            ->countedTowardCvmCap()
            ->sum('grand_total_amount');

        return Money::fromUnscaled($totalDb);
    }

    public function getRemainingCapForUser(User $user, ?int $year = null): ?Money
    {
        $cap = $this->getCapForUser($user, $year);

        if (! $cap instanceof Money) {
            return null;
        }

        $current = $this->getCurrentYearTotalForUser($user, $year);
        $remaining = max(0.0, $cap->toFloat() - $current->toFloat());

        return Money::from($remaining);
    }

    public function canInvest(User $user, Money $proposedAmount, ?int $year = null): bool
    {
        try {
            $this->assertCanInvest($user, $proposedAmount, $year);

            return true;
        } catch (InvestmentCapExceededException) {
            return false;
        }
    }

    public function assertCanInvest(User $user, Money $proposedAmount, ?int $year = null): void
    {
        $cap = $this->getCapForUser($user, $year);

        if (! $cap instanceof Money) {
            return;
        }

        $current = $this->getCurrentYearTotalForUser($user, $year);
        $projected = $current->toFloat() + $proposedAmount->toFloat();

        if ($projected > $cap->toFloat()) {
            throw new InvestmentCapExceededException(
                cap: $cap,
                currentTotal: $current,
                attempted: $proposedAmount,
                remaining: Money::from(max(0.0, $cap->toFloat() - $current->toFloat())),
            );
        }
    }

    /**
     * Variante transacional com lock pessimista nas placements existentes do user
     * para o ano corrente. Use no `CrowdfundingPaymentStep::processStep()` quando
     * já estamos dentro de DB::transaction (via AbstractPlacementStep::handle).
     *
     * Garante que duas requests concorrentes (ex.: dois wizards abertos em paralelo
     * que chegam ao Payment ao mesmo tempo) não consigam exceder o cap.
     */
    public function assertCanInvestLocked(
        User $user,
        Money $proposedAmount,
        ?string $excludePlacementId = null,
        ?int $year = null,
    ): void {
        $cap = $this->getCapForUser($user, $year);

        if (! $cap instanceof Money) {
            return;
        }

        $year ??= Date::now()->year;

        // PostgreSQL doesn't allow FOR UPDATE with aggregate functions.
        // First lock the rows, then sum in a separate query.
        Placement::query()
            ->forUser($user->id)
            ->forCrowdfunding()
            ->forCalendarYear($year)
            ->countedTowardCvmCap()
            ->when($excludePlacementId, fn (Builder $q, string $id) => $q->where('id', '!=', $id))
            ->lockForUpdate()
            ->get(['id']);

        $totalDb = Placement::query()
            ->forUser($user->id)
            ->forCrowdfunding()
            ->forCalendarYear($year)
            ->countedTowardCvmCap()
            ->when($excludePlacementId, fn (Builder $q, string $id) => $q->where('id', '!=', $id))
            ->sum('grand_total_amount');

        $current = Money::fromUnscaled($totalDb);
        $projected = $current->toFloat() + $proposedAmount->toFloat();

        if ($projected > $cap->toFloat()) {
            throw new InvestmentCapExceededException(
                cap: $cap,
                currentTotal: $current,
                attempted: $proposedAmount,
                remaining: Money::from(max(0.0, $cap->toFloat() - $current->toFloat())),
            );
        }
    }
}
