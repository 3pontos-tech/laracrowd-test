<?php

namespace Platform\Offers\Enums;

use App\Enums\Concerns\StringifyEnum;
use Illuminate\Support\Number;

enum OfferModalityEnum: string
{
    use StringifyEnum;

    case Crowdfunding = 'crowdfunding';

    case CommercialPaper = 'commercial-paper';

    public function getInformativeDescription(float $price, ?int $minSharesCount = null): string
    {
        return match ($this) {
            self::Crowdfunding => __('offers::enums.offer_modality.informative_descriptions.minimum_price', [
                'price' => Number::currency($price),
            ]),
            self::CommercialPaper => __('offers::enums.offer_modality.informative_descriptions.shares', [
                'count' => $minSharesCount,
                'price' => Number::currency($price),
            ])
        };
    }

    public function getNextSteps(): array
    {
        return match ($this) {
            self::Crowdfunding => __('offers::enums.offer_modality.next_steps.crowdfunding'),
            self::CommercialPaper => __('offers::enums.offer_modality.next_steps.commercial_paper'),
        };
    }

    public function isCommercialPaper(): bool
    {
        return $this === self::CommercialPaper;
    }

    public function isCrowdfunding(): bool
    {
        return $this === self::Crowdfunding;
    }
}
