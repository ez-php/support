<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\Range;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class RangeTest
 *
 * @package Tests
 */
#[CoversClass(Range::class)]
final class RangeTest extends TestCase
{
    /**
     * @return void
     */
    public function test_of_creates_range(): void
    {
        $r = Range::of(5, 10);
        $this->assertSame(5, $r->min());
        $this->assertSame(10, $r->max());
    }

    /**
     * @return void
     */
    public function test_min_greater_than_max_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Range::of(10, 5);
    }

    /**
     * @return void
     */
    public function test_equal_min_max_is_valid(): void
    {
        $r = Range::of(7, 7);
        $this->assertSame(7, $r->min());
        $this->assertSame(7, $r->max());
    }

    /**
     * @return void
     */
    public function test_contains_value_inside_range(): void
    {
        $r = Range::of(1, 10);
        $this->assertTrue($r->contains(5));
        $this->assertTrue($r->contains(1));
        $this->assertTrue($r->contains(10));
    }

    /**
     * @return void
     */
    public function test_contains_value_outside_range(): void
    {
        $r = Range::of(1, 10);
        $this->assertFalse($r->contains(0));
        $this->assertFalse($r->contains(11));
    }

    /**
     * @return void
     */
    public function test_clamp_returns_value_unchanged_when_inside(): void
    {
        $r = Range::of(0, 100);
        $this->assertSame(50, $r->clamp(50));
    }

    /**
     * @return void
     */
    public function test_clamp_returns_min_when_below(): void
    {
        $r = Range::of(0, 100);
        $this->assertSame(0, $r->clamp(-10));
    }

    /**
     * @return void
     */
    public function test_clamp_returns_max_when_above(): void
    {
        $r = Range::of(0, 100);
        $this->assertSame(100, $r->clamp(150));
    }

    /**
     * @return void
     */
    public function test_random_returns_value_within_range(): void
    {
        $r = Range::of(10, 20);

        for ($i = 0; $i < 50; $i++) {
            $v = $r->random();
            $this->assertGreaterThanOrEqual(10, $v);
            $this->assertLessThanOrEqual(20, $v);
        }
    }

    /**
     * @return void
     */
    public function test_random_single_value_range(): void
    {
        $r = Range::of(42, 42);
        $this->assertSame(42, $r->random());
    }

    /**
     * @return void
     */
    public function test_weighted_low_returns_value_within_range(): void
    {
        $r = Range::of(0, 100);

        for ($i = 0; $i < 50; $i++) {
            $v = $r->weightedLow();
            $this->assertGreaterThanOrEqual(0, $v);
            $this->assertLessThanOrEqual(100, $v);
        }
    }

    /**
     * @return void
     */
    public function test_weighted_low_single_value_range(): void
    {
        $r = Range::of(7, 7);
        $this->assertSame(7, $r->weightedLow());
    }

    /**
     * Statistical sanity check: weightedLow mean should be noticeably below midpoint.
     *
     * @return void
     */
    public function test_weighted_low_skews_towards_lower_end(): void
    {
        $r = Range::of(0, 100);
        $sum = 0;

        for ($i = 0; $i < 1000; $i++) {
            $sum += $r->weightedLow();
        }

        $mean = $sum / 1000;
        // Theoretical mean of min(U, U) on [0,100] is ~33.3
        $this->assertLessThan(45.0, $mean, 'weightedLow mean should be well below midpoint (50)');
    }
}
