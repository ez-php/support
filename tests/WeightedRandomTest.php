<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\WeightedRandom;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class WeightedRandomTest
 *
 * @package Tests
 */
#[CoversClass(WeightedRandom::class)]
final class WeightedRandomTest extends TestCase
{
    // ─── pick ────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_pick_returns_null_for_empty_list(): void
    {
        $this->assertNull(WeightedRandom::pick([]));
    }

    /**
     * @return void
     */
    public function test_pick_returns_only_item(): void
    {
        $item = ['name' => 'A', 'weight' => 1];
        $this->assertSame($item, WeightedRandom::pick([$item]));
    }

    /**
     * @return void
     */
    public function test_pick_only_returns_items_from_list(): void
    {
        $items = [
            ['name' => 'A', 'weight' => 1],
            ['name' => 'B', 'weight' => 2],
            ['name' => 'C', 'weight' => 3],
        ];

        for ($i = 0; $i < 50; $i++) {
            $picked = WeightedRandom::pick($items);
            $this->assertContains($picked, $items);
        }
    }

    /**
     * @return void
     */
    public function test_pick_with_zero_weights_returns_first_item(): void
    {
        $items = [
            ['name' => 'A', 'weight' => 0],
            ['name' => 'B', 'weight' => 0],
        ];
        $this->assertSame($items[0], WeightedRandom::pick($items));
    }

    /**
     * Statistical: item with weight 0 should never be selected when others have weight > 0.
     *
     * @return void
     */
    public function test_pick_zero_weight_item_never_selected(): void
    {
        $items = [
            ['name' => 'never', 'weight' => 0],
            ['name' => 'always', 'weight' => 100],
        ];

        for ($i = 0; $i < 100; $i++) {
            $picked = WeightedRandom::pick($items);
            $this->assertNotNull($picked);
            $this->assertSame('always', $picked['name']);
        }
    }

    /**
     * Statistical: higher-weight item should be selected more often.
     *
     * @return void
     */
    public function test_pick_respects_relative_weights(): void
    {
        $items = [
            ['name' => 'rare', 'weight' => 1],
            ['name' => 'common', 'weight' => 9],
        ];

        $counts = ['rare' => 0, 'common' => 0];

        for ($i = 0; $i < 1000; $i++) {
            $picked = WeightedRandom::pick($items);
            $this->assertNotNull($picked);
            $name = $picked['name'];
            $this->assertIsString($name);
            $counts[$name]++;
        }

        // common should be ~9x more frequent; allow generous bounds
        $this->assertGreaterThan(700, $counts['common']);
        $this->assertLessThan(300, $counts['rare']);
    }

    /**
     * @return void
     */
    public function test_pick_uses_custom_weight_key(): void
    {
        $items = [
            ['label' => 'A', 'w' => 0],
            ['label' => 'B', 'w' => 100],
        ];

        for ($i = 0; $i < 20; $i++) {
            $picked = WeightedRandom::pick($items, 'w');
            $this->assertNotNull($picked);
            $this->assertSame('B', $picked['label']);
        }
    }

    // ─── pickN ───────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_pick_n_returns_correct_count(): void
    {
        $items = [
            ['name' => 'A', 'weight' => 1],
            ['name' => 'B', 'weight' => 1],
            ['name' => 'C', 'weight' => 1],
            ['name' => 'D', 'weight' => 1],
        ];
        $this->assertCount(2, WeightedRandom::pickN($items, 2));
    }

    /**
     * @return void
     */
    public function test_pick_n_returns_all_when_n_exceeds_count(): void
    {
        $items = [
            ['name' => 'A', 'weight' => 1],
            ['name' => 'B', 'weight' => 1],
        ];
        $this->assertCount(2, WeightedRandom::pickN($items, 5));
    }

    /**
     * @return void
     */
    public function test_pick_n_no_duplicates(): void
    {
        $items = [
            ['name' => 'A', 'weight' => 1],
            ['name' => 'B', 'weight' => 1],
            ['name' => 'C', 'weight' => 1],
        ];
        $picked = WeightedRandom::pickN($items, 3);
        $names = array_map(static function (array $item): string {
            $n = $item['name'] ?? '';

            return is_string($n) ? $n : '';
        }, $picked);
        $this->assertCount(count(array_unique($names)), $names);
    }

    /**
     * @return void
     */
    public function test_pick_n_empty_list_returns_empty(): void
    {
        $this->assertSame([], WeightedRandom::pickN([], 3));
    }

    // ─── weightedLow ─────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_weighted_low_stays_in_range(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $v = WeightedRandom::weightedLow(10, 20);
            $this->assertGreaterThanOrEqual(10, $v);
            $this->assertLessThanOrEqual(20, $v);
        }
    }

    /**
     * @return void
     */
    public function test_weighted_low_single_value(): void
    {
        $this->assertSame(5, WeightedRandom::weightedLow(5, 5));
    }

    /**
     * Statistical sanity: mean should be below midpoint.
     *
     * @return void
     */
    public function test_weighted_low_skews_low(): void
    {
        $sum = 0;

        for ($i = 0; $i < 1000; $i++) {
            $sum += WeightedRandom::weightedLow(0, 100);
        }

        $mean = $sum / 1000;
        $this->assertLessThan(45.0, $mean);
    }
}
