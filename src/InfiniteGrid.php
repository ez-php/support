<?php

declare(strict_types=1);

namespace EzPhp\Support;

/**
 * Stateless utility for infinite 2D tile grid arithmetic using the Chebyshev metric.
 *
 * The Chebyshev distance between two tiles is max(|Δx|, |Δy|), which makes diagonals
 * and cardinals equidistant. This matches the 8-directional movement model common in
 * tile-based games.
 *
 * @package EzPhp\Support
 */
final class InfiniteGrid
{
    /**
     * InfiniteGrid constructor — not instantiable; all methods are static.
     */
    private function __construct()
    {
    }

    /**
     * Return the eight tiles directly adjacent to (x, y) — the Moore neighbourhood.
     *
     * The center tile itself is excluded.
     *
     * @param int $x
     * @param int $y
     *
     * @return list<Coordinate>
     */
    public static function adjacentCoordinates(int $x, int $y): array
    {
        $result = [];

        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dy = -1; $dy <= 1; $dy++) {
                if ($dx === 0 && $dy === 0) {
                    continue;
                }

                $result[] = new Coordinate($x + $dx, $y + $dy);
            }
        }

        return $result;
    }

    /**
     * Return whether two tiles are adjacent (Chebyshev distance exactly 1).
     *
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     *
     * @return bool
     */
    public static function isAdjacent(int $x1, int $y1, int $x2, int $y2): bool
    {
        return self::chebyshevDistance($x1, $y1, $x2, $y2) === 1;
    }

    /**
     * Return the Chebyshev distance between two tiles: max(|Δx|, |Δy|).
     *
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     *
     * @return int
     */
    public static function chebyshevDistance(int $x1, int $y1, int $x2, int $y2): int
    {
        return max(abs($x2 - $x1), abs($y2 - $y1));
    }

    /**
     * Return all tiles within Chebyshev distance $radius from (x, y), including the center.
     *
     * Returns an empty list when radius < 0.
     * With radius = 0 returns only the center tile.
     * With radius = 1 returns the center plus its 8 neighbours — 9 tiles total.
     *
     * @param int $x
     * @param int $y
     * @param int $radius
     *
     * @return list<Coordinate>
     */
    public static function tilesInRadius(int $x, int $y, int $radius): array
    {
        if ($radius < 0) {
            return [];
        }

        $result = [];

        for ($dx = -$radius; $dx <= $radius; $dx++) {
            for ($dy = -$radius; $dy <= $radius; $dy++) {
                $result[] = new Coordinate($x + $dx, $y + $dy);
            }
        }

        return $result;
    }
}
