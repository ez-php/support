<?php

declare(strict_types=1);

namespace EzPhp\Support;

use InvalidArgumentException;

/**
 * Class TimeProbability
 *
 * Exponential time-based probability: the longer since the last event, the more likely
 * the next one fires — up to a hard cap that guarantees it eventually.
 *
 * Formula: P(t) = 1 − exp(−t / λ)
 *
 * @package EzPhp\Support
 */
final class TimeProbability
{
    /**
     * TimeProbability constructor — not instantiable; all methods are static.
     */
    private function __construct()
    {
    }

    /**
     * Compute the probability at time t without rolling.
     *
     * Returns 1.0 when minutesSince >= hardCapMinutes.
     *
     * @param float $minutesSince   Minutes elapsed since the reference point
     * @param float $lambda         Time constant (λ): the mean time to ~63% probability
     * @param int   $hardCapMinutes Elapsed minutes at which probability is forced to 1.0
     *
     * @return float Probability in [0.0, 1.0]
     */
    public static function probability(
        float $minutesSince,
        float $lambda = 4.0,
        int $hardCapMinutes = 15,
    ): float {
        if ($lambda <= 0.0) {
            throw new InvalidArgumentException('lambda must be greater than 0.');
        }

        if ($minutesSince >= $hardCapMinutes) {
            return 1.0;
        }

        if ($minutesSince <= 0.0) {
            return 0.0;
        }

        return 1.0 - exp(-$minutesSince / $lambda);
    }

    /**
     * Roll the exponential probability curve and return true/false.
     *
     * Always returns true once minutesSince >= hardCapMinutes.
     *
     * @param float $minutesSince   Minutes elapsed since the reference point
     * @param float $lambda         Time constant (λ): the mean time to ~63% probability
     * @param int   $hardCapMinutes Elapsed minutes at which the event is guaranteed
     *
     * @return bool
     */
    public static function exponential(
        float $minutesSince,
        float $lambda = 4.0,
        int $hardCapMinutes = 15,
    ): bool {
        $p = self::probability($minutesSince, $lambda, $hardCapMinutes);

        return $p >= 1.0 || (mt_rand() / mt_getrandmax()) < $p;
    }
}
