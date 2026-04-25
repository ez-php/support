<?php

declare(strict_types=1);

namespace EzPhp\Support;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Class DailyQuota
 *
 * Immutable value object for the "daily allowance + per-action growing cooldown + UTC midnight reset"
 * pattern. State-changing operations return new instances; the caller persists the updated state.
 *
 * Usage:
 *   $quota = new DailyQuota(dailyLimit: 5, cooldownBaseSeconds: 300, cooldownStepSeconds: 300);
 *   $now   = new DateTimeImmutable();
 *
 *   if ($quota->canPerform($now)) {
 *       $quota = $quota->perform($now);
 *       // persist $quota state
 *   }
 *
 * @package EzPhp\Support
 */
final class DailyQuota
{
    /**
     * @param int                    $dailyLimit           Max actions allowed per UTC day
     * @param int                    $cooldownBaseSeconds  Minimum cooldown after first action (seconds)
     * @param int                    $cooldownStepSeconds  Additional cooldown per subsequent action
     * @param int                    $usedToday            Actions already performed today
     * @param DateTimeImmutable|null $cooldownUntil        When the next action becomes available
     * @param DateTimeImmutable|null $lastReset            UTC midnight timestamp of the last reset
     */
    public function __construct(
        private readonly int $dailyLimit,
        private readonly int $cooldownBaseSeconds,
        private readonly int $cooldownStepSeconds,
        private readonly int $usedToday = 0,
        private readonly ?DateTimeImmutable $cooldownUntil = null,
        private readonly ?DateTimeImmutable $lastReset = null,
    ) {
        if ($dailyLimit < 1) {
            throw new InvalidArgumentException('dailyLimit must be at least 1.');
        }

        if ($cooldownBaseSeconds < 0 || $cooldownStepSeconds < 0) {
            throw new InvalidArgumentException('Cooldown seconds must be non-negative.');
        }
    }

    /**
     * Reset the daily counter if the current UTC day differs from the last reset day.
     *
     * Returns a new instance with usedToday=0 and cooldownUntil=null when a reset is due,
     * or the same instance unchanged when already on the same UTC day.
     *
     * @param DateTimeImmutable $now
     *
     * @return self
     */
    public function resetIfNeeded(DateTimeImmutable $now): self
    {
        $utc = new DateTimeZone('UTC');
        $todayMidnight = new DateTimeImmutable(
            $now->setTimezone($utc)->format('Y-m-d'),
            $utc,
        );

        if ($this->lastReset === null || $todayMidnight > $this->lastReset) {
            return new self(
                $this->dailyLimit,
                $this->cooldownBaseSeconds,
                $this->cooldownStepSeconds,
                0,
                null,
                $todayMidnight,
            );
        }

        return $this;
    }

    /**
     * Return whether an action can be performed right now.
     * Implicitly applies any pending UTC midnight reset before checking.
     *
     * @param DateTimeImmutable $now
     *
     * @return bool
     */
    public function canPerform(DateTimeImmutable $now): bool
    {
        $state = $this->resetIfNeeded($now);

        if ($state->usedToday >= $state->dailyLimit) {
            return false;
        }

        return $state->cooldownUntil === null || $state->cooldownUntil <= $now;
    }

    /**
     * Record one performed action and return the updated quota.
     *
     * Implicitly applies any pending UTC midnight reset, then increments the counter
     * and schedules the next cooldown: base + (step × newUsedToday).
     *
     * @param DateTimeImmutable $now
     *
     * @return self
     */
    public function perform(DateTimeImmutable $now): self
    {
        $state = $this->resetIfNeeded($now);
        $newUsed = $state->usedToday + 1;
        $cooldownSecs = $state->cooldownBaseSeconds + $state->cooldownStepSeconds * $newUsed;
        $cooldown = $now->add(new DateInterval(sprintf('PT%dS', $cooldownSecs)));

        return new self(
            $state->dailyLimit,
            $state->cooldownBaseSeconds,
            $state->cooldownStepSeconds,
            $newUsed,
            $cooldown,
            $state->lastReset,
        );
    }

    /**
     * Return the number of actions still available today.
     *
     * @return int
     */
    public function remaining(): int
    {
        return max(0, $this->dailyLimit - $this->usedToday);
    }

    /**
     * @return int
     */
    public function usedToday(): int
    {
        return $this->usedToday;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function cooldownUntil(): ?DateTimeImmutable
    {
        return $this->cooldownUntil;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function lastReset(): ?DateTimeImmutable
    {
        return $this->lastReset;
    }
}
