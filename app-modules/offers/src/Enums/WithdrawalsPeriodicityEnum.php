<?php

namespace Platform\Offers\Enums;

use App\Enums\Concerns\StringifyEnum;
use InvalidArgumentException;

enum WithdrawalsPeriodicityEnum: string
{
    use StringifyEnum;

    case Monthly = 'monthly';
    case Bimonthly = 'bimonthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case SemiAnnual = 'semiannual';
    case AtMaturity = 'at_maturity';

    public static function fromInt(int $periodicity): self
    {
        return match ($periodicity) {
            0 => self::AtMaturity,
            1 => self::Monthly,
            2 => self::Bimonthly,
            3 => self::Quarterly,
            6 => self::SemiAnnual,
            12 => self::Yearly,
            default => throw new InvalidArgumentException('Invalid periodicity value'),
        };

    }

    public static function fromPeriodicity(string $periodicity): self
    {
        return match ($periodicity) {
            'Mensal' => self::Monthly,
            'Bimestral' => self::Bimonthly,
            'Trimestral' => self::Quarterly,
            'Semestral' => self::SemiAnnual,
            'Anual' => self::Yearly,
            'No vencimento' => self::AtMaturity,
        };
    }

    public function getPeriodicity(): int
    {
        return match ($this) {
            self::AtMaturity => 0 ,
            self::Monthly => 1,
            self::Bimonthly => 2,
            self::Quarterly => 3,
            self::Yearly => 12,
            self::SemiAnnual => 6,
        };
    }
}
