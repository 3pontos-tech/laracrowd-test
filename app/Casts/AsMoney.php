<?php

namespace App\Casts;

use App\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Money\Exception\MoneyMismatchException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;

class AsMoney implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return Money
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {

        return Money::fromUnscaled($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws MoneyMismatchException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {

        if ($value instanceof BigInteger) {
            return $value->toInt();
        }

        if ($value instanceof BigDecimal || $value instanceof BigInteger) {
            return $value->getUnscaledValue()->toInt();
        }

        if ($value instanceof Money) {
            return $value->toDatabase();
        }

        if (is_int($value)) {
            return BigDecimal::ofUnscaledValue($value, $this->getScale())->getUnscaledValue()->toInt();
        }

        if (is_float($value)) {
            return BigDecimal::of($value)->toScale($this->getScale())->getUnscaledValue()->toInt();
        }

        if ($value instanceof Stringable) {
            return BigDecimal::of($value)->toScale($this->getScale())->getUnscaledValue()->toInt();
        }

        throw new MoneyMismatchException(
            sprintf('Invalid value for money cast: %s (typeof %s)', $value, gettype($value))
        );
    }

    private function getScale(): int
    {
        return config('platform.money.scale', 7);
    }
}
