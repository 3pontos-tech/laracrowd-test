<?php

declare(strict_types=1);

namespace Platform\Placements\Enums;

use App\Enums\Concerns\StringifyEnum;

enum PlacementCompletedKind: string
{
    use StringifyEnum;

    case ContractBreached = 'contract_breached'; // The placement was completed due to a breach of contract by the startup.

    case UserTerminated = 'user_terminated'; // The placement was completed because the user terminated it.

    case CvmCapExceeded = 'cvm_cap_exceeded'; // The placement was cancelled automatically because it would exceed the CVM 88 annual investment cap.

    case CvmWithdrawal = 'cvm_withdrawal'; // Investor exercised CVM 88 Art. 3 withdrawal right within 5-day grace period.

}
