<?php

namespace Platform\Placements\Enums\Contracts;

use App\Enums\Concerns\StringifyEnum;

enum ContractTemplateTypeEnum: string
{
    use StringifyEnum;

    case RiskAgreement = 'risk_agreement';
    case Contract = 'contract';
    case Termination = 'termination';
}
