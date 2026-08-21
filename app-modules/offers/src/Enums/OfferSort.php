<?php

declare(strict_types=1);

namespace Platform\Offers\Enums;

use App\Enums\Concerns\StringifyEnum;

/**
 * Os critérios pelos quais o investidor pode ordenar a listagem de ofertas.
 *
 * Cada caso é um par (campo, direção) com nome de negócio: "menor risco" diz
 * mais que "risk_level asc", e é o nome que aparece no seletor.
 */
enum OfferSort: string
{
    use StringifyEnum;

    case Newest = 'newest';
    case HighestReturn = 'highest_return';
    case LowestRisk = 'lowest_risk';
    case SoonestMaturity = 'soonest_maturity';
    case ClosingSoonest = 'closing_soonest';

    /**
     * Agrupa visualmente as ofertas do mesmo setor deixando-as adjacentes na
     * grade. É o único critério que não vive na oferta — o segmento é da
     * startup — e o único sobre texto livre: o campo é digitado no admin, sem
     * lista fechada, então "Fintech" e "fintech" ordenam como setores distintos.
     */
    case Segment = 'segment';

    public static function default(): self
    {
        return self::Newest;
    }
}
