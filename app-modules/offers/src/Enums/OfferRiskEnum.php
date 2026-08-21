<?php

namespace Platform\Offers\Enums;

use App\Enums\Concerns\StringifyEnum;
use InvalidArgumentException;

enum OfferRiskEnum: string
{
    use StringifyEnum;

    case VeryLow = 'very_low_risk';
    case Low = 'low_risk';
    case Moderate = 'moderate_risk';
    case High = 'high_risk';
    case VeryHigh = 'very_high_risk';

    public static function fromInt(int $value): self
    {
        return match ($value) {
            1 => self::VeryLow,
            2 => self::Low,
            3 => self::Moderate,
            4 => self::High,
            5 => self::VeryHigh,
            default => throw new InvalidArgumentException('Invalid risk level value'),
        };
    }

    /**
     * A posição do risco na escala, do mais brando ao mais severo. O enum já
     * sabia converter de inteiro (`fromInt`); isto é o caminho de volta, e existe
     * porque `risk_level` guarda strings — ordenar por elas daria "alto" antes de
     * "baixo".
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::VeryLow => 1,
            self::Low => 2,
            self::Moderate => 3,
            self::High => 4,
            self::VeryHigh => 5,
        };
    }
}
