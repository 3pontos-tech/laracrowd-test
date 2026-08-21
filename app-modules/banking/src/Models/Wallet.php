<?php

namespace Platform\Banking\Models;

use App\Casts\AsMoney;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Banking\Database\Factories\WalletFactory;
use Platform\Banking\Enums\Wallet\WalletOwnerKind;
use Platform\Banking\Enums\Wallet\WalletStatus;
use Platform\Banking\Models\Transactions\Transaction;

/**
 * @property Money $balance
 * @property int|string $id
 * @property Money $available_earnings Only touched through transactions
 * @property Money $total_earnings Only touched through transactions
 * @property Money $total_withdrawn Only touched through transactions
 * @property Money $total_invested Only touched through transactions
 * @property WalletStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Wallet extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'wallets';

    protected $fillable = [
        'ownable_id',
        'ownable_type',
        'currency',
        'status',
        'balance',
        'total_withdrawn',
        'total_earnings',
        'available_earnings',
        'total_invested',
        'total_withdrawn',
    ];

    protected $casts = [
        'status' => WalletStatus::class,
        'ownable_type' => WalletOwnerKind::class,
        'balance' => AsMoney::class,
        'total_earnings' => AsMoney::class,
        'available_earnings' => AsMoney::class,
        'total_invested' => AsMoney::class,
        'total_withdrawn' => AsMoney::class,
    ];

    public function ownable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }
}
