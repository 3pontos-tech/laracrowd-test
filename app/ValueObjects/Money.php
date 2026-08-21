<?php

namespace App\ValueObjects;

use Brick\Math\BigDecimal;
use Illuminate\Support\Number;

class Money
{
    public function __construct(
        public BigDecimal $amount,
    ) {}

    public static function from(mixed $amount): self
    {
        if ($amount instanceof BigDecimal) {
            return new self($amount);
        }

        if (is_string($amount)) {
            $amount = str($amount)->replace(',', '');
        }

        $decimal = BigDecimal::of($amount);

        if ($decimal->getScale() !== self::getScale()) {
            return new self($decimal->toScale(self::getScale()));
        }

        $decimal = BigDecimal::ofUnscaledValue($amount ?? 0, self::getScale());

        return new self(
            amount: $decimal,
        );
    }

    public static function getScale(): int
    {
        return config('platform.money.scale', 6) ?? 6;
    }

    public static function fromUnscaled(mixed $amount): self
    {
        if (is_string($amount)) {
            $amount = str($amount)->replace(',', '');
        }

        $decimal = BigDecimal::ofUnscaledValue($amount ?? 0, self::getScale());

        return new self(
            amount: $decimal,
        );
    }

    public function toFloat(): float
    {
        return $this->amount->toFloat();
    }

    public function toDatabase(): int
    {
        return $this->amount->getUnscaledValue()->toInt();
    }

    public function toString(): string
    {
        return Number::currency($this->toFloat());
    }
}
