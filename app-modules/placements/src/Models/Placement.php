<?php

namespace Platform\Placements\Models;

use App\Casts\AsMoney;
use App\Models\Users\User;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Number;
use Platform\Banking\Actions\WalletRentability\PlacementMonthlyRentabilityAction;
use Platform\Banking\Models\Wallet;
use Platform\Offers\Models\Offer;
use Platform\Placements\Actions\AbstractPlacementStep;
use Platform\Placements\Database\Factories\PlacementFactory;
use Platform\Placements\Enums\PlacementCompletedKind;
use Platform\Placements\Enums\PlacementProcess;
use Platform\Placements\Enums\PlacementStatus;
use Platform\Placements\Scopes\PlacementScopes;

/**
 * @property Offer $offer
 * @property Wallet|null $wallet
 * @property string $slug
 * @property Money $grand_total_amount
 * @property PlacementProcess $process
 * @property PlacementCompletedKind $completion_reason
 * @property AbstractPlacementStep $current_step
 * @property PlacementStatus $status
 * @property Date $placement_starting_at
 */
class Placement extends Model
{
    use HasFactory;
    use HasUuids;
    use PlacementScopes;

    protected $table = 'user_placements';

    protected $fillable = [
        'user_id',
        'offer_id',
        'status',
        'process',
        'slug',
        'grand_total_amount',
        'shares_purchased',
        'placement_payment_at',
        'placement_starting_at',
        'placement_finished_at',
        'completion_reason',
        'completion_notes',
        'automatic_withdrawal',
        'total_withdrawn_enabled',
        'withdrawal_requested_at',
    ];

    protected $appends = [
        'profit_percentage',
    ];

    protected $casts = [
        'status' => PlacementStatus::class,
        'process' => PlacementProcess::class,
        'completion_reason' => PlacementCompletedKind::class,
        'grand_total_amount' => AsMoney::class,
        'placement_payment_at' => 'datetime',
        'placement_starting_at' => 'datetime',
        'placement_finished_at' => 'datetime',
        'withdrawal_requested_at' => 'datetime',
        'automatic_withdrawal' => 'boolean',
        'total_withdrawn_enabled' => 'boolean',
    ];

    /**
     * Get the question that owns the poll option.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function contract(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'placements_contracts');
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'ownable');
    }

    public function processMonthlyRentability(
        bool $dryRun = true
    ): array {
        $action = resolve(PlacementMonthlyRentabilityAction::class);

        $period = Date::now()->subMonth();

        return $action->handle($this, $period, $dryRun)->toArray();
    }

    /**
     * Verifica se o placement pode sacar no mês informado.
     *
     * Regras aplicadas (Opção A):
     *  - A periodicidade é contada a partir de placement_starting_at
     *  - A carência (withdraw_grace_period_in_months) também é contada a partir de placement_starting_at
     *  - O saque é permitido quando o mês alvo contém a ocorrência "n * periodicidade" desde o placement_starting_at
     *
     * @param  CarbonInterface  $month  Um Carbon representando qualquer dia no mês alvo (usamos só year/month)
     */
    public function canWithdrawForMonth(CarbonInterface $month): bool
    {
        if (! $this->offer || ! $this->placement_starting_at) {
            return false;
        }

        $periodicityInMonths = $this->offer->withdraw_periodicity->getPeriodicity(); // 1,2,3,6,12
        $graceMonths = (int) $this->offer->withdraw_grace_period_in_months;

        // "No vencimento" nao tem periodicidade (getPeriodicity() === 0): nao
        // existe mes intermediario de saque, e o modulo la embaixo estouraria.
        if ($periodicityInMonths <= 0) {
            return false;
        }

        // Normalizar: trabalhar com cópias para não alterar atributos do model
        $start = Date::parse($this->placement_starting_at);
        $targetMonth = Date::parse($month)->startOfMonth();
        $targetMonthEnd = (clone $targetMonth)->endOfMonth();

        // Se o start for posterior ao mês alvo, não pode
        if ($start->gt($targetMonthEnd)) {
            //            dump('Se o start for posterior ao mês alvo, não pode');

            return false;
        }

        // Carência: exige que a data start + graceMonths caia antes ou dentro do mês alvo
        $graceEndsAt = (clone $start)->addMonths($graceMonths)->day(1);
        //        dump('target-month-end: ' . $targetMonthEnd->format('Y-m-d H:i:s'));
        if ($graceEndsAt->gt($targetMonthEnd)) {
            //            dump($graceEndsAt->format('Y-m-d H:i:s'));
            //            dump('Carência: exige que a data start + graceMonths caia antes ou dentro do mês alvo');

            return false;
        }

        // Diferença em meses entre start e target (inteira, baseada em year/month)
        $monthsDifference = ($targetMonth->year - $start->year) * 12 + ($targetMonth->month - $start->month);

        if ($monthsDifference < 0) {
            //            dump('Diferença em meses entre start e target (inteira, baseada em year/month)');

            return false;
        }

        // É permitido se monthsDifference for múltiplo exato da periodicidade
        return $monthsDifference % $periodicityInMonths === 0;
    }

    protected static function newFactory(): PlacementFactory
    {
        return PlacementFactory::new();
    }

    protected function monthlyRentability(): Attribute
    {
        return Attribute::make(get: fn () => once(fn () => $this->processMonthlyRentability()));
    }

    protected function getProfitPercentageAttribute(): string
    {
        $balance = $this->wallet->earnings_balance ?? 0.0;
        $grandTotal = $this->grand_total_amount->toFloat();

        if ($balance <= 0 || $grandTotal <= 0) {
            return '0.00';
        }

        return Number::format(($balance / $grandTotal) * 100, 2);
    }

    protected function roi(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $invested = $this->wallet?->total_invested?->toFloat() ?? 0.0;
                $earnings = $this->wallet?->total_earnings?->toFloat() ?? 0.0;

                if ($invested <= 0) {
                    return Number::percentage(0, 2);
                }

                $roi = $earnings / $invested * 100;

                return Number::percentage($roi, 2);
            }
        );
    }

    protected function currentStep(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status->getAction($this),
        );
    }
}
