<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Tests\Unit;

use FelixMuhoro\Mpesa\Enums\ResultCode;
use PHPUnit\Framework\TestCase;

class ResultCodeTest extends TestCase
{
    public function test_zero_is_completed(): void
    {
        $this->assertTrue(ResultCode::isCompleted('0'));
        $this->assertFalse(ResultCode::isPending('0'));
        $this->assertFalse(ResultCode::isFailed('0'));
    }

    public function test_known_failures(): void
    {
        foreach (['1', '1032', '2001', '1019', '1037'] as $code) {
            $this->assertTrue(ResultCode::isFailed($code), "Expected $code to be failed");
        }
    }

    public function test_undocumented_4999_is_pending_not_failed(): void
    {
        $this->assertTrue(ResultCode::isPending('4999'));
        $this->assertFalse(ResultCode::isFailed('4999'));
    }

    public function test_unknown_code_defaults_to_pending(): void
    {
        $this->assertTrue(ResultCode::isPending('99999'));
        $this->assertFalse(ResultCode::isFailed('99999'));
    }

    public function test_empty_code_is_pending(): void
    {
        $this->assertTrue(ResultCode::isPending(''));
        $this->assertTrue(ResultCode::isPending(null));
    }

    public function test_resolve_returns_status_message_and_code(): void
    {
        $resolved = ResultCode::resolve('1032');
        $this->assertSame(ResultCode::STATUS_FAILED, $resolved['status']);
        $this->assertSame('1032', $resolved['code']);
        $this->assertStringContainsString('cancelled', strtolower($resolved['message']));
    }
}
