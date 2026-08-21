<?php

namespace Platform\Offers\Models;

use App\Casts\AsMoney;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Offers\Database\Factories\RateFactory;

/**
 * @property Money $value
 * @property Money $initial_bond_value
 * @property bool $can_pay
 */
class Rate extends Model
{
    use HasFactory;

    protected $table = 'startup_offer_rates';

    protected $fillable = [
        'startup_offer_id',
        'value',
        'starting_at',
        'initial_bond_value',
        'current_bond_value',
        'residual_bond_value',
        'can_pay',
    ];

    protected $casts = [
        'starting_at' => 'date',
        'value' => AsMoney::class,
        'initial_bond_value' => AsMoney::class,
        'current_bond_value' => AsMoney::class,
        'residual_bond_value' => AsMoney::class,
        'can_pay' => 'boolean',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'startup_offer_id');
    }

    protected static function newFactory(): RateFactory
    {
        return RateFactory::new();
    }
}
