<?php

declare(strict_types=1);

namespace EzPhp\Support;

use InvalidArgumentException;

/**
 * Class Range
 *
 * Immutable value object for a bounded integer range.
 * Provides containment checks, clamping, uniform random draw, and a min-of-two-draws
 * variant that skews the distribution towards the lower end.
 *
 * @package EzPhp\Support
 */
final class Range
{
    /**
     * @param int $min Lower bound (inclusive)
     * @param int $max Upper bound (inclusive)
     */
    public function __construct(
        private readonly int $min,
        private readonly int $max,
    ) {
        if ($min > $max) {
            throw new InvalidArgumentException("min ($min) must not be greater than max ($max).");
        }
    }

    /**
     * Named constructor.
     *
     * @param int $min
     * @param int $max
     *
     * @return self
     */
    public static function of(int $min, int $max): self
    {
        return new self($min, $max);
    }

    /**
     * @return int
     */
    public function min(): int
    {
        return $this->min;
    }

    /**
     * @return int
     */
    public function max(): int
    {
        return $this->max;
    }

    /**
     * Return whether the value falls within [min, max].
     *
     * @param int $value
     *
     * @return bool
     */
    public function contains(int $value): bool
    {
        return $value >= $this->min && $value <= $this->max;
    }

    /**
     * Clamp the value to [min, max].
     *
     * @param int $value
     *
     * @return int
     */
    public function clamp(int $value): int
    {
        return max($this->min, min($this->max, $value));
    }

    /**
     * Draw a uniformly distributed random integer in [min, max].
     *
     * @return int
     */
    public function random(): int
    {
        return random_int($this->min, $this->max);
    }

    /**
     * Draw a random integer skewed towards the lower end.
     * Uses min-of-two-draws: returns the smaller of two independent uniform draws.
     *
     * @return int
     */
    public function weightedLow(): int
    {
        return min(random_int($this->min, $this->max), random_int($this->min, $this->max));
    }
}
