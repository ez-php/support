<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\Coordinate;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class CoordinateTest
 *
 * @package Tests
 */
#[CoversClass(Coordinate::class)]
final class CoordinateTest extends TestCase
{
    /**
     * @return void
     */
    public function test_stores_x_and_y(): void
    {
        $coord = new Coordinate(3, -7);

        $this->assertSame(3, $coord->x);
        $this->assertSame(-7, $coord->y);
    }

    /**
     * @return void
     */
    public function test_stores_zero_values(): void
    {
        $coord = new Coordinate(0, 0);

        $this->assertSame(0, $coord->x);
        $this->assertSame(0, $coord->y);
    }

    /**
     * @return void
     */
    public function test_stores_large_negative_values(): void
    {
        $coord = new Coordinate(-1000, -2000);

        $this->assertSame(-1000, $coord->x);
        $this->assertSame(-2000, $coord->y);
    }
}
