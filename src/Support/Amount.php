<?php

declare(strict_types=1);

namespace Edydeyemi\Paystack\Support;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\NumberSyntax;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Utility helpers for converting between major currency units (e.g. NGN, GHS, ZAR, USD)
 * and Paystack's lowest denomination minor units (e.g. kobo, pesewas, cents).
 */
final class Amount
{
    private const int MAX_PRECISION = 18;

    private const int MAX_INPUT_DIGITS = 128;

    /**
     * Convert a major currency amount (e.g. 1500.50 NGN) to minor units (e.g. 150050 kobo).
     *
     * @param  int|float|string  $majorAmount  Monetary value in major units.
     * @param  int  $precision  Number of decimal places for the currency (default: 2).
     *
     * @throws InvalidArgumentException When the amount is invalid, precision is unsupported, or it cannot fit in a PHP integer.
     */
    public static function toMinor(int|float|string $majorAmount, int $precision = 2): int
    {
        if (! is_numeric($majorAmount)) {
            throw new InvalidArgumentException('Amount must be numeric.');
        }

        $precision = self::assertPrecision($precision);

        try {
            return BigDecimal::parse(
                trim((string) $majorAmount),
                NumberSyntax::SCIENTIFIC,
                self::MAX_INPUT_DIGITS,
            )
                ->multipliedBy(BigDecimal::ten()->power($precision))
                ->toScale(0, RoundingMode::HalfUp)
                ->toInt();
        } catch (MathException $e) {
            throw new InvalidArgumentException(
                'Amount is outside the supported numeric range or precision.',
                previous: $e,
            );
        }
    }

    /**
     * Convert minor units (e.g. 150050 kobo) to a major currency float (e.g. 1500.50).
     *
     * @param  int  $minorAmount  Monetary value in minor units.
     * @param  int  $precision  Number of decimal places for the currency (default: 2).
     *
     * @throws InvalidArgumentException When precision is outside the supported range.
     */
    public static function toMajor(int $minorAmount, int $precision = 2): float
    {
        $precision = self::assertPrecision($precision);

        return $minorAmount / (10 ** $precision);
    }

    /**
     * Format minor units as a human-readable currency string (e.g. "NGN 1,500.50").
     *
     * @param  int  $minorAmount  Monetary value in minor units.
     * @param  string|null  $currency  ISO currency code; defaults to `config('paystack.currency')`.
     * @param  int  $precision  Number of decimal places for formatting (default: 2).
     */
    public static function format(int $minorAmount, ?string $currency = null, int $precision = 2): string
    {
        $resolvedCurrency = $currency ?? (string) config('paystack.currency', 'NGN');
        $precision = self::assertPrecision($precision);
        $major = BigDecimal::ofUnscaledValue($minorAmount, $precision)
            ->toScale($precision)
            ->toString();
        [$whole, $fraction] = array_pad(explode('.', $major, 2), 2, '');
        $negative = str_starts_with($whole, '-');
        $wholeDigits = $negative ? substr($whole, 1) : $whole;
        $groups = [];

        while (strlen($wholeDigits) > 3) {
            array_unshift($groups, substr($wholeDigits, -3));
            $wholeDigits = substr($wholeDigits, 0, -3);
        }

        array_unshift($groups, $wholeDigits);

        $formatted = ($negative ? '-' : '').implode(',', $groups);

        if ($precision > 0) {
            $formatted .= '.'.$fraction;
        }

        return trim($resolvedCurrency.' '.$formatted);
    }

    /**
     * @return int<0, 18>
     */
    private static function assertPrecision(int $precision): int
    {
        if ($precision < 0 || $precision > self::MAX_PRECISION) {
            throw new InvalidArgumentException(
                'Precision must be between 0 and '.self::MAX_PRECISION.'.'
            );
        }

        return $precision;
    }
}
