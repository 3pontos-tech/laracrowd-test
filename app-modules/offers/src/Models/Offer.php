<?php

namespace Platform\Offers\Models;

use App\Casts\AsMoney;
use App\Models\Users\User;
use App\ValueObjects\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Offers\Database\Factories\OfferFactory;
use Platform\Offers\DTOs\OfferFundingProgress;
use Platform\Offers\Enums\OfferModalityEnum;
use Platform\Offers\Enums\OfferRiskEnum;
use Platform\Offers\Enums\OfferSort;
use Platform\Offers\Enums\OfferStatusEnum;
use Platform\Offers\Enums\StartupOfferAmortization;
use Platform\Offers\Enums\WithdrawalsPeriodicityEnum;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Models\Placement;
use Platform\Placements\Models\Template;

/**
 * @property string $name
 * @property string $slug
 * @property string $reference
 * @property Rate|null $currentRate
 * @property OfferStatusEnum $status
 * @property OfferModalityEnum $modality_type
 * @property WithdrawalsPeriodicityEnum $withdraw_periodicity
 * @property OfferRiskEnum $risk_level
 * @property Money $total_value
 * @property Money $min_investment
 * @property Carbon $capturing_until
 * @property int $min_shares_count
 * @property int $total_shares_count
 * @property int|null $available_shares_count
 * @property Money|null $total_invested_amount
 * @property int $grand_total_amount
 * @property Collection<int, Placement> $placements
 */
#[UseFactory(OfferFactory::class)]
class Offer extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * Aportes que já contam como capital captado pela oferta.
     */
    private const array FUNDED_PLACEMENT_STATUSES = [
        PlacementStatus::Active,
        PlacementStatus::Finished,
    ];

    protected $table = 'startup_offers';

    protected $casts = [
        'status' => OfferStatusEnum::class,
        'modality_type' => OfferModalityEnum::class,
        'withdraw_periodicity' => WithdrawalsPeriodicityEnum::class,
        'risk_level' => OfferRiskEnum::class,
        'total_value' => AsMoney::class,
        'min_investment' => AsMoney::class,
        'capturing_until' => 'datetime',
        'roi' => 'float',
        'duration_in_months' => 'integer',
        'total_shares_count' => 'integer',
        'active' => 'boolean',
        'visible' => 'boolean',
        'visible_on_logged' => 'boolean',
        'visible_for_investor' => 'boolean',
        'amortization' => StartupOfferAmortization::class,
    ];

    protected $hidden = [
        'total_invested_amount',
        'total_reserved_amount',
    ];

    protected $fillable = [
        'startup_id',
        'modality_type',
        'name',
        'slug',
        'reference',
        'status',
        'active',
        'visible',
        'visible_on_logged',
        'visible_for_investor',
        'duration_in_months',
        'capturing_until',
        'finish_at',
        'risk_level',
        'roi',
        'total_value',
        'min_investment',
        'min_shares_count',
        'total_shares_count',
        'withdraw_percentage_limit',
        'withdraw_periodicity',
        'withdraw_grace_period_in_months',
        'remuneration',
        'amortization',
        'application_quotation',
        'liquidation_event',
    ];

    protected $appends = [
        'percentage_invested',
        'total_invested_amount',
    ];

    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class);
    }

    public function contractTemplate(): BelongsToMany
    {
        return $this->belongsToMany(
            Template::class,
            'offer_contract',
            'offer_id',
            'contract_template_id'
        );
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'startup_offer_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'offer_id');
    }

    public function hasActiveInvestor(User $user): bool
    {
        return $this->placements()
            ->where('user_id', $user->id)
            ->where('status', PlacementStatus::Active)
            ->exists();
    }

    public function currentRate(): HasOne
    {
        $currentDate = now();
        if ($this->modality_type === OfferModalityEnum::Crowdfunding) {
            $currentDate = $currentDate->subMonth();

            return $this
                ->hasOne(Rate::class, 'startup_offer_id')
                ->whereYear('starting_at', $currentDate->year)
                ->whereMonth('starting_at', $currentDate->month)
                ->latest('starting_at');
        }

        return $this
            ->hasOne(Rate::class, 'startup_offer_id')
            ->whereDate('starting_at', '=', $currentDate);
    }

    #[Scope]
    protected function visibleInCrowdfundingPanel(Builder $query): void
    {
        $query
            ->whereNot('status', OfferStatusEnum::Draft)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q
                    ->whereIn('status', [OfferStatusEnum::Active, OfferStatusEnum::Terminated])
                    ->where(fn ($q) => $q->where('visible', true)->orWhere('visible_on_logged', true))
                )
                ->orWhere(fn ($q) => $q
                    ->where('visible_for_investor', true)
                    ->whereIn('status', [OfferStatusEnum::Active, OfferStatusEnum::Finished, OfferStatusEnum::Terminated])
                    ->whereHas('placements', fn ($q) => $q
                        ->where('user_id', auth()->id())
                        ->where('status', PlacementStatus::Active)
                    )
                )
            );
    }

    #[Scope]
    protected function visibleOnLandingPage(Builder $query): void
    {
        $query
            ->whereIn('status', [OfferStatusEnum::Active, OfferStatusEnum::Terminated])
            ->where('visible', true)
            ->where('visible_on_logged', false);
    }

    /**
     * Anexa a captação da oferta por subquery, em reais e em cotas. É de
     * propósito que os agregados não passem pela relação `placements` já
     * carregada: a listagem de "Minhas Ofertas" traz essa relação filtrada pelo
     * usuário logado (`withWhereHas`), e somar por ali devolveria só o que o
     * próprio investidor aportou.
     */
    #[Scope]
    protected function withFundingProgress(Builder $query): void
    {
        $query
            ->withSum(
                ['placements as funded_amount' => fn (Builder $query) => $query->whereIn('status', self::FUNDED_PLACEMENT_STATUSES)],
                'grand_total_amount'
            )
            ->withSum(
                ['placements as funded_shares' => fn (Builder $query) => $query->whereIn('status', self::FUNDED_PLACEMENT_STATUSES)],
                'shares_purchased'
            );
    }

    #[Scope]
    protected function sortedBy(Builder $query, OfferSort $sort): void
    {
        match ($sort) {
            OfferSort::Newest => $query->latest(),
            OfferSort::HighestReturn => $query->orderByDesc('roi'),
            OfferSort::LowestRisk => $this->orderByRiskSortExpression($query),
            OfferSort::SoonestMaturity => $query->orderBy('duration_in_months'),
            OfferSort::ClosingSoonest => $query->orderBy('capturing_until'),
            OfferSort::Segment => $this->orderBySegment($query),
        };
    }

    /**
     * @see withFundingProgress() — aplique o escopo na listagem para a grade
     *      resolver os agregados em uma query só; sem ele, cada card cai no
     *      `loadSum` e paga uma ida ao banco.
     */
    protected function fundingProgress(): Attribute
    {
        return Attribute::make(get: function (): OfferFundingProgress {
            if (! array_key_exists('funded_amount', $this->attributes)) {
                $this->loadFundedSums();
            }

            $captured = Money::fromUnscaled($this->attributes['funded_amount'] ?? 0);

            return new OfferFundingProgress(
                captured: $captured,
                goal: $this->total_value,
                percentage: $this->resolveFundedPercentage($captured),
            );
        });
    }

    protected function totalInvestedAmount(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->modality_type->isCommercialPaper()) {
                return $this->placements
                    ->whereIn('status', [PlacementStatus::Active, PlacementStatus::Finished])
                    ->sum(fn (Placement $data) => $data->grand_total_amount->toFloat());
            }

            return Money::fromUnscaled($this->placements
                ->whereIn('status', [PlacementStatus::Active, PlacementStatus::Finished])
                ->sum(fn (Placement $data) => $data->grand_total_amount->toDatabase()));
        });
    }

    protected function totalReservedAmount(): Attribute
    {
        return Attribute::make(get: fn () => Money::fromUnscaled($this->placements
            ->whereNotIn('status', [PlacementStatus::Active, PlacementStatus::Finished])
            ->sum(fn (Placement $data) => $data->grand_total_amount->toDatabase())));
    }

    protected function availableAmountToInvest(): Attribute
    {
        return Attribute::make(get: function (): int|float {
            if ($this->modality_type->isCommercialPaper()) {
                return 0;
            }

            return $this->total_value->amount->minus($this->total_invested_amount->amount)->toFloat();
        });
    }

    protected function percentageInvested(): Attribute
    {
        return Attribute::make(get: function (): float {
            if ($this->modality_type->isCommercialPaper()) {
                if (! $this->total_shares_count) {
                    return 0.0;
                }

                $percentage = $this->placements()
                    ->where('status', [PlacementStatus::Active])
                    ->sum('shares_purchased') / $this->total_shares_count * 100;
            } else {

                if ($this->total_value instanceof \Stringable) {
                    $this->total_value = Money::from($this->total_value);
                }

                if ($this->total_value->amount->isZero()) {
                    return 0.0;
                }

                $percentage = $this->total_invested_amount
                    ->amount
                    ->dividedBy($this->total_value->amount, 2, RoundingMode::HALF_EVEN)
                    ->multipliedBy(100)
                    ->toFloat();
            }

            if ($percentage > 100) {
                return 100.0;
            }

            return round($percentage, 2);
        });
    }

    protected function availableSharesCount(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if ($this->modality_type !== OfferModalityEnum::CommercialPaper || ! $this->total_shares_count) {
                    return null;
                }

                $soldShares = $this->placements
                    ->whereNotIn('status', [PlacementStatus::Draft])
                    ->sum(fn (Placement $placement) => $placement->shares_purchased);

                $availableShares = $this->total_shares_count - $soldShares;

                return max(0, $availableShares);
            }
        );
    }

    private function loadFundedSums(): void
    {
        $funded = fn (Builder $query) => $query->whereIn('status', self::FUNDED_PLACEMENT_STATUSES);

        $this->loadSum(['placements as funded_amount' => $funded], 'grand_total_amount');
        $this->loadSum(['placements as funded_shares' => $funded], 'shares_purchased');
    }

    /**
     * A securitização mede captação em cotas vendidas e o crowdfunding em
     * reais: é a mesma regra que a página de detalhe da oferta já usa, para os
     * dois lugares não mostrarem percentuais diferentes da mesma oferta.
     */
    private function resolveFundedPercentage(Money $captured): float
    {
        if ($this->modality_type->isCommercialPaper()) {
            if (! $this->total_shares_count) {
                return 0.0;
            }

            $percentage = (int) ($this->attributes['funded_shares'] ?? 0)
                / $this->total_shares_count
                * 100;
        } else {
            if ($this->total_value->amount->isZero()) {
                return 0.0;
            }

            $percentage = $captured->amount
                ->dividedBy($this->total_value->amount, 4, RoundingMode::HALF_EVEN)
                ->multipliedBy(100)
                ->toFloat();
        }

        return round(min($percentage, 100), 2);
    }

    /**
     * O segmento é da startup, não da oferta. A ordenação sai por subquery
     * correlacionada em vez de join: join na `startup` mudaria o shape do SELECT
     * e passaria a duplicar linha assim que a cadeia ganhasse qualquer relação
     * um-para-muitos, quebrando a contagem de cards da grade.
     */
    private function orderBySegment(Builder $query): void
    {
        $query->orderBy(
            Startup::query()
                ->select('segment')
                ->whereColumn('startup.id', $this->qualifyColumn('startup_id'))
        );
    }

    /**
     * Monta o CASE a partir dos próprios cases do enum: um risco novo entra na
     * ordenação sozinho, e nenhuma string do banco fica solta na query. Os
     * valores viajam como bindings — não interpolados na string — e a coluna é
     * qualificada, para não ficar ambígua se a query ganhar um join. O ELSE
     * cobre tanto um valor gravado que não exista mais no enum quanto
     * `risk_level` nulo: em SQL, nenhum WHEN casa com NULL, então os dois casos
     * vão para o fim em vez de derrubar a listagem.
     */
    private function orderByRiskSortExpression(Builder $query): void
    {
        $cases = OfferRiskEnum::cases();

        $sql = sprintf(
            'CASE %s %s ELSE 99 END',
            $this->qualifyColumn('risk_level'),
            implode(' ', array_fill(0, count($cases), 'WHEN ? THEN ?')),
        );

        $bindings = collect($cases)
            ->flatMap(fn (OfferRiskEnum $risk): array => [$risk->value, $risk->sortOrder()])
            ->all();

        $query->orderByRaw($sql, $bindings);
    }
}
