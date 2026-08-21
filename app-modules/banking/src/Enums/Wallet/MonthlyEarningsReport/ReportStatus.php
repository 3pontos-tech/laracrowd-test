<?php

namespace Platform\Banking\Enums\Wallet\MonthlyEarningsReport;

use App\Enums\Concerns\StringifyEnum;

enum ReportStatus: string
{
    use StringifyEnum;

    case Processed = 'processed';
    case Skipped = 'skipped';

}
