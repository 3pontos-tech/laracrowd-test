<?php

namespace Platform\Banking\Enums\Wallet\MonthlyEarningsReport;

use App\Enums\Concerns\StringifyEnum;

enum SkipReason: string
{
    use StringifyEnum;

    case MissingRate = 'missing_rate';
    case BalanceZeroOrNegative = 'balance_zero_or_negative';
    case AlreadyProcessed = 'already_processed';
    case EarningZeroOrNegative = 'earning_zero_or_negative';

    case EarningsNextMonth = 'earnings_next_month';
    case PendingWithdrawals = 'pending_withdrawals';

}
