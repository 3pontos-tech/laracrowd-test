<?php

declare(strict_types=1);

namespace Platform\Offers\Enums;

use App\Enums\Concerns\StringifyEnum;

enum StartupOfferAmortization: string
{
    use StringifyEnum;

    case Monthly = 'Mensal';
    case Bimonthly = 'Bimestral';
    case Quarterly = 'Trimestral';
    case SemiAnnual = 'Semestral';
    case Annual = 'Anual';
    case AtMaturity = 'No vencimento';

}
