<?php

declare(strict_types=1);

namespace EzPhp\Support;

/**
 * The result of applying XP to a character, returned by XpProgression::applyXp().
 *
 * @package EzPhp\Support
 */
final readonly class LevelUpResult
{
    /**
     * @param int $newXp    Total accumulated XP after the gain.
     * @param int $newLevel Level after any promotions.
     * @param int $levelUps How many levels were gained in this single call.
     */
    public function __construct(
        private int $newXp,
        private int $newLevel,
        private int $levelUps,
    ) {
    }

    /**
     * @return int
     */
    public function newXp(): int
    {
        return $this->newXp;
    }

    /**
     * @return int
     */
    public function newLevel(): int
    {
        return $this->newLevel;
    }

    /**
     * @return int
     */
    public function levelUps(): int
    {
        return $this->levelUps;
    }

    /**
     * Whether at least one level was gained.
     *
     * @return bool
     */
    public function didLevelUp(): bool
    {
        return $this->levelUps > 0;
    }
}
