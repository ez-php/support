<?php

declare(strict_types=1);

namespace EzPhp\Support;

use InvalidArgumentException;

/**
 * A configurable exponential XP curve for levelling systems.
 *
 * The XP threshold for reaching level N is: floor(base × N^exponent).
 * XP is never reset on level-up — it accumulates indefinitely, making
 * multi-level-up calls during a single applyXp() safe and consistent.
 *
 * @package EzPhp\Support
 */
final readonly class XpProgression
{
    /**
     * @param int   $base     XP required to reach level 1. Must be >= 1.
     * @param float $exponent Growth steepness. 1.0 = linear, > 1.0 = progressively harder. Must be > 0.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private int $base = 100,
        private float $exponent = 1.5,
    ) {
        if ($base < 1) {
            throw new InvalidArgumentException('base must be at least 1.');
        }

        if ($exponent <= 0.0) {
            throw new InvalidArgumentException('exponent must be greater than 0.');
        }
    }

    /**
     * Return the total XP required to reach the given level.
     *
     * Returns 0 for level < 1.
     *
     * @param int $level
     *
     * @return int
     */
    public function xpForLevel(int $level): int
    {
        if ($level < 1) {
            return 0;
        }

        return (int) floor($this->base * ($level ** $this->exponent));
    }

    /**
     * Apply gained XP to a character and return the resulting state.
     *
     * Handles multi-level-ups in a single call. XP is never reduced on level-up.
     *
     * @param int $currentXp    Current accumulated XP.
     * @param int $currentLevel Current level (typically >= 1).
     * @param int $gained       XP points to add. May be 0 or negative.
     *
     * @return LevelUpResult
     */
    public function applyXp(int $currentXp, int $currentLevel, int $gained): LevelUpResult
    {
        $xp = $currentXp + $gained;
        $level = $currentLevel;
        $levelUps = 0;

        while ($xp >= $this->xpForLevel($level + 1)) {
            $level++;
            $levelUps++;
        }

        return new LevelUpResult($xp, $level, $levelUps);
    }
}
