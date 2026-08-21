<?php

namespace Platform\Placements\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Placements\Enums\Contracts\ContractTemplateTypeEnum;

class Template extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'contract_templates';

    protected $fillable = ['name', 'template_type'];

    protected $casts = [
        'template_type' => ContractTemplateTypeEnum::class,
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_template_id');
    }
}
