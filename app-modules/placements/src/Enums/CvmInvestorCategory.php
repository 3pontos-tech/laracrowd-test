<?php

declare(strict_types=1);

namespace Platform\Placements\Enums;

enum CvmInvestorCategory: string
{
    case Lead = 'lead';
    case Qualified = 'qualified';
    case HighIncome = 'high_income';
    case Standard = 'standard';

    public function isUnlimited(): bool
    {
        return in_array($this, [self::Lead, self::Qualified], true);
    }
}
