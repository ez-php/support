<?php

declare(strict_types=1);

namespace EzPhp\Support;

/**
 * An immutable 2D integer coordinate on an infinite grid.
 *
 * @package EzPhp\Support
 */
final readonly class Coordinate
{
    /**
     * @param int $x Horizontal position (positive = right).
     * @param int $y Vertical position (positive = up).
     */
    public function __construct(
        public int $x,
        public int $y,
    ) {
    }
}
