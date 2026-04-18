<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Tests\Unit;

use FelixMuhoro\Mpesa\Exceptions\MpesaException;
use FelixMuhoro\Mpesa\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('validFormats')]
    public function test_it_normalises_valid_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::format($input));
    }

    public static function validFormats(): array
    {
        return [
            'leading zero'     => ['0712345678',     '254712345678'],
            'nine digits'      => ['712345678',      '254712345678'],
            'canonical'        => ['254712345678',   '254712345678'],
            'with plus'        => ['+254712345678',  '254712345678'],
            'with spaces'      => ['254 712 345 678','254712345678'],
            'with hyphens'     => ['+254-712-345-678','254712345678'],
            'safaricom 1xx range' => ['110345678',   '254110345678'],
        ];
    }

    public function test_it_rejects_invalid_numbers(): void
    {
        $this->expectException(MpesaException::class);
        PhoneNumber::format('not-a-phone');
    }

    public function test_it_rejects_too_short(): void
    {
        $this->expectException(MpesaException::class);
        PhoneNumber::format('12345');
    }

    public function test_is_valid_returns_bool(): void
    {
        $this->assertTrue(PhoneNumber::isValid('0712345678'));
        $this->assertFalse(PhoneNumber::isValid('abc'));
    }
}
