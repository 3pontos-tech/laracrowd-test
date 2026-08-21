<?php

namespace Platform\Banking\Models\Transactions;

use App\Casts\AsMoney;
use App\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Banking\Database\Factories\Transactions\TransactionFactory;
use Platform\Banking\Enums\Transactions\TransactionEntryType;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Models\Wallet;
use Platform\Banking\Scopes\TransactionScopes;

/**
 * @property Money $amount
 * @property TransactionEntryType $entry_type
 * @property TransactionKind $transaction_kind
 */
class Transaction extends Model
{
    use HasFactory;
    use HasUuids;
    use TransactionScopes;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'ledger_id',
        'wallet_id',
        'entry_type',
        'transaction_kind',
        'amount',
        'currency',
        'description',
        'reference',
        'metadata',
        'entry_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'amount' => AsMoney::class,
        'transaction_kind' => TransactionKind::class,
        'entry_type' => TransactionEntryType::class,
    ];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function signedAmount(): BigDecimal
    {
        return $this->entry_type === TransactionEntryType::Credit
            ? $this->amount->amount
            : $this->amount->amount->negated();
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
