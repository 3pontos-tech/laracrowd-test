<?php

namespace App\Models\Finance;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Placements\Enums\CvmInvestorCategory;

class FinanceProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Threshold above which the investor is no longer bound to the flat annual cap
     * and instead gets a percentage-based one.
     */
    public const HIGH_INCOME_THRESHOLD = 200_000.00;

    protected $table = 'user_financial';

    protected $fillable = [
        'user_id',
        'annual_gross_income',
        'financial_investments_amount',
        'is_qualified_investor',
    ];

    protected $casts = [
        'annual_gross_income' => 'decimal:2',
        'financial_investments_amount' => 'decimal:2',
        'is_qualified_investor' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isQualifiedInvestor(): bool
    {
        return (bool) $this->is_qualified_investor;
    }

    public function cvmCategory(): CvmInvestorCategory
    {
        if ($this->isQualifiedInvestor()) {
            return CvmInvestorCategory::Qualified;
        }

        $income = (float) ($this->annual_gross_income ?? 0);
        $investments = (float) ($this->financial_investments_amount ?? 0);

        if ($income >= self::HIGH_INCOME_THRESHOLD || $investments >= self::HIGH_INCOME_THRESHOLD) {
            return CvmInvestorCategory::HighIncome;
        }

        return CvmInvestorCategory::Standard;
    }
}
