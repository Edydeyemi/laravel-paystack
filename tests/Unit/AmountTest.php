<?php

declare(strict_types=1);

use Edydeyemi\Paystack\Support\Amount;

it('converts major amounts to minor units', function () {
    expect(Amount::toMinor(1500))->toBe(150000)
        ->and(Amount::toMinor(1500.50))->toBe(150050)
        ->and(Amount::toMinor('99.99'))->toBe(9999)
        ->and(Amount::toMinor(10.123, 3))->toBe(10123);
});

it('converts minor units to major amounts', function () {
    expect(Amount::toMajor(150050))->toBe(1500.5)
        ->and(Amount::toMajor(50000))->toBe(500.0);
});

it('formats minor units using configured or explicit currency', function () {
    expect(Amount::format(150050))->toBe('NGN 1,500.50')
        ->and(Amount::format(150050, 'GHS'))->toBe('GHS 1,500.50')
        ->and(Amount::format(PHP_INT_MAX))->toBe('NGN 92,233,720,368,547,758.07')
        ->and(Amount::format(12345, 'JPY', 0))->toBe('JPY 12,345');
});

it('throws on non-numeric or negative precision', function () {
    expect(fn () => Amount::toMinor('abc'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Amount::toMinor(100, -1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Amount::toMajor(100, -1))->toThrow(InvalidArgumentException::class);
});

it('converts decimal strings exactly and rejects overflow or excessive precision', function () {
    expect(Amount::toMinor('1.005'))->toBe(101)
        ->and(Amount::toMinor('1e3'))->toBe(100000)
        ->and(fn () => Amount::toMinor('999999999999999999999999.99'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Amount::toMinor('1e1000000000'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Amount::toMinor('1', 19))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Amount::toMajor(100, 19))
        ->toThrow(InvalidArgumentException::class);
});
