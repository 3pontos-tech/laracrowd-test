<?php

namespace Platform\Banking\Models\Transactions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Banking\Database\Factories\Transactions\LedgerFactory;
use Platform\Banking\Enums\Transactions\TransactionKind;
use Platform\Banking\Enums\Transactions\TransactionStatus;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'reference',
        'status',
        'entry_at',
        'metadata',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function newFactory(): LedgerFactory
    {
        return LedgerFactory::new();
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'type' => TransactionKind::class,
            'status' => TransactionStatus::class,
        ];
    }
}
