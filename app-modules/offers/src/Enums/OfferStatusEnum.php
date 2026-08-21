<?php

namespace Platform\Offers\Enums;

use App\Enums\Concerns\StringifyEnum;

enum OfferStatusEnum: string
{
    use StringifyEnum;

    case Draft = 'draft';
    case Active = 'active';
    case Terminated = 'terminated';
    case Finished = 'finished';

    /**
     * O `<x-tag>` tem paleta própria — orange, green, lime, blue e pink — e cai
     * em lime sem avisar quando recebe qualquer outra cor. Por isso o mapa é
     * explícito aqui, em vez de reaproveitar o Color::* de getColor().
     */
    public function getTagColor(): string
    {
        return match ($this) {
            self::Draft => 'orange',
            self::Active => 'green',
            self::Terminated => 'pink',
            self::Finished => 'blue',
        };
    }
}
