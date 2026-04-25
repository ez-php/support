<?php

declare(strict_types=1);

namespace EzPhp\Support;

/**
 * Class WeightedRandom
 *
 * Stateless utility for weighted random selection.
 * Items are associative arrays that must carry a numeric weight under a configurable key.
 *
 * @package EzPhp\Support
 */
final class WeightedRandom
{
    /**
     * WeightedRandom constructor — not instantiable; all methods are static.
     */
    private function __construct()
    {
    }

    /**
     * Pick one item from the list according to its weight.
     * Items with higher weights are proportionally more likely to be selected.
     * Returns null when the list is empty.
     *
     * @param list<array<string, mixed>> $items
     * @param string                     $weightKey
     *
     * @return array<string, mixed>|null
     */
    public static function pick(array $items, string $weightKey = 'weight'): ?array
    {
        if ($items === []) {
            return null;
        }

        $total = self::totalWeight($items, $weightKey);

        if ($total <= 0.0) {
            return $items[array_key_first($items)];
        }

        $rand = (mt_rand() / mt_getrandmax()) * $total;
        $cumulative = 0.0;
        $last = null;

        foreach ($items as $item) {
            $w = $item[$weightKey] ?? 0;
            $cumulative += is_numeric($w) ? (float) $w : 0.0;
            $last = $item;

            if ($rand < $cumulative) {
                return $item;
            }
        }

        return $last;
    }

    /**
     * Pick N distinct items from the list without replacement, weighted by their weight key.
     * If N exceeds the number of items, all items are returned (in weighted order).
     *
     * @param list<array<string, mixed>> $items
     * @param int                        $n
     * @param string                     $weightKey
     *
     * @return list<array<string, mixed>>
     */
    public static function pickN(array $items, int $n, string $weightKey = 'weight'): array
    {
        $results = [];
        $pool = $items;

        for ($i = 0; $i < $n && $pool !== []; $i++) {
            $picked = self::pick($pool, $weightKey);

            if ($picked === null) {
                break;
            }

            $results[] = $picked;

            foreach ($pool as $k => $item) {
                if ($item === $picked) {
                    array_splice($pool, $k, 1);
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Draw a random integer in [min, max] skewed towards the lower end.
     * Uses min-of-two-draws: returns the smaller of two independent uniform draws.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public static function weightedLow(int $min, int $max): int
    {
        return min(mt_rand($min, $max), mt_rand($min, $max));
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param string                     $weightKey
     *
     * @return float
     */
    private static function totalWeight(array $items, string $weightKey): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $w = $item[$weightKey] ?? 0;
            $total += is_numeric($w) ? (float) $w : 0.0;
        }

        return $total;
    }
}
