<?php

namespace Platform\Placements\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Platform\Placements\Enums\Contracts\ContractStatusEnum;
use Platform\Placements\Enums\Contracts\ContractTemplateTypeEnum;

class Contract extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'contracts';

    protected $fillable = [
        'contract_template_id',
        'status',
        'external_id',
        'signed_at',
    ];

    protected $casts = [
        'status' => ContractStatusEnum::class,
        'signed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'contract_template_id');
    }

    public function placements(): BelongsToMany
    {
        return $this->belongsToMany(Placement::class, 'placements_contracts');
    }

    public function isContract(): bool
    {
        return $this->template->template_type === ContractTemplateTypeEnum::Contract;
    }

    public function markAsSigned(): void
    {
        $this->update([
            'status' => ContractStatusEnum::Signed,
            'signed_at' => now(),
        ]);
    }

    public function markAsRejected(): void
    {
        $this->update(['status' => ContractStatusEnum::Rejected]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => ContractStatusEnum::Cancelled]);
    }
}
