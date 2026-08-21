<?php

declare(strict_types=1);

namespace Platform\Offers\DTOs;

use App\ValueObjects\Money;

/**
 * Progresso de captação de uma oferta, já resolvido: quanto entrou, qual é a
 * meta e o percentual pronto para virar largura de barra. Quem decide se o
 * percentual sai de cotas ou de reais é a modalidade, no accessor do Offer —
 * a view recebe o número e desenha.
 */
final readonly class OfferFundingProgress
{
    public function __construct(
        public Money $captured,
        public Money $goal,
        public float $percentage,
    ) {}
}
