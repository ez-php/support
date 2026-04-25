<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\Coordinate;
use EzPhp\Support\InfiniteGrid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class InfiniteGridTest
 *
 * @package Tests
 */
#[CoversClass(InfiniteGrid::class)]
#[UsesClass(Coordinate::class)]
final class InfiniteGridTest extends TestCase
{
    // ─── adjacentCoordinates ─────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_adjacent_coordinates_returns_eight_neighbors(): void
    {
        $this->assertCount(8, InfiniteGrid::adjacentCoordinates(0, 0));
    }

    /**
     * @return void
     */
    public function test_adjacent_coordinates_excludes_center(): void
    {
        $neighbors = InfiniteGrid::adjacentCoordinates(5, 5);

        foreach ($neighbors as $c) {
            $this->assertFalse($c->x === 5 && $c->y === 5, 'Center tile must not appear in neighbors.');
        }
    }

    /**
     * @return void
     */
    public function test_adjacent_coordinates_all_at_chebyshev_distance_one(): void
    {
        $neighbors = InfiniteGrid::adjacentCoordinates(3, 4);

        foreach ($neighbors as $c) {
            $this->assertSame(1, InfiniteGrid::chebyshevDistance(3, 4, $c->x, $c->y));
        }
    }

    /**
     * @return void
     */
    public function test_adjacent_coordinates_at_negative_origin(): void
    {
        $neighbors = InfiniteGrid::adjacentCoordinates(-2, -3);

        $this->assertCount(8, $neighbors);
    }

    // ─── isAdjacent ──────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_is_adjacent_true_for_cardinal_neighbor(): void
    {
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, 1, 0));
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, 0, 1));
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, -1, 0));
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, 0, -1));
    }

    /**
     * @return void
     */
    public function test_is_adjacent_true_for_diagonal_neighbor(): void
    {
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, 1, 1));
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, -1, -1));
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, 1, -1));
        $this->assertTrue(InfiniteGrid::isAdjacent(0, 0, -1, 1));
    }

    /**
     * @return void
     */
    public function test_is_adjacent_false_for_same_tile(): void
    {
        $this->assertFalse(InfiniteGrid::isAdjacent(3, 3, 3, 3));
    }

    /**
     * @return void
     */
    public function test_is_adjacent_false_for_distance_two(): void
    {
        $this->assertFalse(InfiniteGrid::isAdjacent(0, 0, 2, 0));
        $this->assertFalse(InfiniteGrid::isAdjacent(0, 0, 2, 2));
    }

    // ─── chebyshevDistance ───────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_chebyshev_distance_same_tile(): void
    {
        $this->assertSame(0, InfiniteGrid::chebyshevDistance(5, 5, 5, 5));
    }

    /**
     * @return void
     */
    public function test_chebyshev_distance_cardinal(): void
    {
        $this->assertSame(3, InfiniteGrid::chebyshevDistance(0, 0, 3, 0));
        $this->assertSame(3, InfiniteGrid::chebyshevDistance(0, 0, 0, 3));
    }

    /**
     * @return void
     */
    public function test_chebyshev_distance_diagonal_equals_max_axis(): void
    {
        // (0,0) to (3,2): max(3,2) = 3
        $this->assertSame(3, InfiniteGrid::chebyshevDistance(0, 0, 3, 2));
    }

    /**
     * @return void
     */
    public function test_chebyshev_distance_symmetric(): void
    {
        $this->assertSame(
            InfiniteGrid::chebyshevDistance(1, 2, 5, 4),
            InfiniteGrid::chebyshevDistance(5, 4, 1, 2),
        );
    }

    /**
     * @return void
     */
    public function test_chebyshev_distance_negative_coordinates(): void
    {
        $this->assertSame(4, InfiniteGrid::chebyshevDistance(-2, -2, 2, 2));
    }

    // ─── tilesInRadius ───────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_tiles_in_radius_negative_returns_empty(): void
    {
        $this->assertSame([], InfiniteGrid::tilesInRadius(0, 0, -1));
    }

    /**
     * @return void
     */
    public function test_tiles_in_radius_zero_returns_only_center(): void
    {
        $tiles = InfiniteGrid::tilesInRadius(4, 7, 0);

        $this->assertCount(1, $tiles);
        $this->assertSame(4, $tiles[0]->x);
        $this->assertSame(7, $tiles[0]->y);
    }

    /**
     * @return void
     */
    public function test_tiles_in_radius_one_returns_nine_tiles(): void
    {
        $this->assertCount(9, InfiniteGrid::tilesInRadius(0, 0, 1));
    }

    /**
     * @return void
     */
    public function test_tiles_in_radius_two_returns_twenty_five_tiles(): void
    {
        $this->assertCount(25, InfiniteGrid::tilesInRadius(0, 0, 2));
    }

    /**
     * @return void
     */
    public function test_tiles_in_radius_all_within_chebyshev_distance(): void
    {
        $tiles = InfiniteGrid::tilesInRadius(3, 3, 2);

        foreach ($tiles as $tile) {
            $this->assertLessThanOrEqual(2, InfiniteGrid::chebyshevDistance(3, 3, $tile->x, $tile->y));
        }
    }

    /**
     * @return void
     */
    public function test_tiles_in_radius_includes_center(): void
    {
        $tiles = InfiniteGrid::tilesInRadius(5, 5, 3);
        $hasCenter = false;

        foreach ($tiles as $tile) {
            if ($tile->x === 5 && $tile->y === 5) {
                $hasCenter = true;
                break;
            }
        }

        $this->assertTrue($hasCenter);
    }
}
