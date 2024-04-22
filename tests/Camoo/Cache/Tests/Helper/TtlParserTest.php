<?php

declare(strict_types=1);

namespace Camoo\Cache\Tests\Helper;

use Camoo\Cache\Exception\AppCacheException;
use Camoo\Cache\Helper\TtlParser;
use DateInterval;
use PHPUnit\Framework\TestCase;

class TtlParserTest extends TestCase
{
    private TtlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TtlParser();
    }

    public function testParseNullTtlReturnsNull(): void
    {
        $this->assertNull($this->parser->toDateInterval(null));
    }

    public function testParseDateIntervalReturnsSameInstance(): void
    {
        $interval = new DateInterval('PT10S');
        $this->assertSame($interval, $this->parser->toDateInterval($interval));
    }

    public function testParseValidStringTtlReturnsDateInterval(): void
    {
        $interval = $this->parser->toDateInterval('+1 minute');
        $this->assertEquals(60, $interval->s);
    }

    public function testParseInvalidStringThrowsException(): void
    {
        $this->expectException(AppCacheException::class);
        $this->expectExceptionMessage('Invalid TTL value: Must start with +');
        $this->parser->toDateInterval('1 minute');
    }

    public function testParseNegativeTtlThrowsException(): void
    {
        $this->expectException(AppCacheException::class);
        $this->expectExceptionMessage('Invalid TTL value: Must start with +');
        $this->parser->toDateInterval('-1 minute');
    }

    public function testParseIntegerTtlReturnsDateInterval(): void
    {
        $interval = $this->parser->toDateInterval(60);
        $this->assertInstanceOf(DateInterval::class, $interval);
        $this->assertEquals(60, $interval->s);
    }

    public function testToSecondsWithNullReturnsNull(): void
    {
        $this->assertNull($this->parser->toSeconds(null));
    }

    public function testToSecondsWithIntegerReturnsSameValue(): void
    {
        $seconds = 3600;
        $this->assertSame($seconds, $this->parser->toSeconds($seconds));
    }

    public function testToSecondsWithDateIntervalReturnsCorrectSeconds(): void
    {
        $interval = new DateInterval('PT3600S');
        $this->assertEquals(3600, $this->parser->toSeconds($interval));
    }

    public function testToSecondsWithStringReturnsCorrectSeconds(): void
    {
        $this->assertEquals(60, $this->parser->toSeconds('+1 minute'));
    }

    public function testToSecondsWithInvalidStringThrowsException(): void
    {
        $this->expectException(AppCacheException::class);
        $this->expectExceptionMessage('Invalid TTL value: Must start with +');
        $this->parser->toSeconds('1 minute');
    }

    public function testToSecondsWithNegativeIntervalThrowsException(): void
    {
        $this->expectException(AppCacheException::class);
        $this->expectExceptionMessage('Invalid TTL value: Must start with +');
        $this->parser->toSeconds('-1 minute');
    }
}
