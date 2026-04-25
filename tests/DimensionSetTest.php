<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\DimensionSet;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DimensionSetTest
 *
 * @package Tests
 */
#[CoversClass(DimensionSet::class)]
final class DimensionSetTest extends TestCase
{
    // ─── constructor ─────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_constructor_clamps_above_100(): void
    {
        $set = new DimensionSet(['rage' => 150]);

        $this->assertSame(100, $set->get('rage'));
    }

    /**
     * @return void
     */
    public function test_constructor_clamps_below_zero(): void
    {
        $set = new DimensionSet(['joy' => -30]);

        $this->assertSame(0, $set->get('joy'));
    }

    /**
     * @return void
     */
    public function test_constructor_preserves_values_in_range(): void
    {
        $set = new DimensionSet(['str' => 0, 'dex' => 50, 'int' => 100]);

        $this->assertSame(0, $set->get('str'));
        $this->assertSame(50, $set->get('dex'));
        $this->assertSame(100, $set->get('int'));
    }

    // ─── get ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_get_returns_zero_for_missing_dimension(): void
    {
        $set = new DimensionSet([]);

        $this->assertSame(0, $set->get('nonexistent'));
    }

    // ─── apply ───────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_apply_returns_new_instance(): void
    {
        $original = new DimensionSet(['heat' => 50]);
        $modified = $original->apply(['heat' => 10]);

        $this->assertNotSame($original, $modified);
    }

    /**
     * @return void
     */
    public function test_apply_does_not_mutate_original(): void
    {
        $original = new DimensionSet(['joy' => 40]);
        $original->apply(['joy' => 20]);

        $this->assertSame(40, $original->get('joy'));
    }

    /**
     * @return void
     */
    public function test_apply_increases_dimension(): void
    {
        $set = new DimensionSet(['trust' => 30]);

        $this->assertSame(50, $set->apply(['trust' => 20])->get('trust'));
    }

    /**
     * @return void
     */
    public function test_apply_decreases_dimension(): void
    {
        $set = new DimensionSet(['trust' => 30]);

        $this->assertSame(10, $set->apply(['trust' => -20])->get('trust'));
    }

    /**
     * @return void
     */
    public function test_apply_clamps_at_100(): void
    {
        $set = new DimensionSet(['anger' => 90]);

        $this->assertSame(100, $set->apply(['anger' => 50])->get('anger'));
    }

    /**
     * @return void
     */
    public function test_apply_clamps_at_zero(): void
    {
        $set = new DimensionSet(['calm' => 10]);

        $this->assertSame(0, $set->apply(['calm' => -50])->get('calm'));
    }

    /**
     * @return void
     */
    public function test_apply_creates_new_dimension_from_zero(): void
    {
        $set = new DimensionSet([]);

        $this->assertSame(25, $set->apply(['new' => 25])->get('new'));
    }

    /**
     * @return void
     */
    public function test_apply_preserves_untouched_dimensions(): void
    {
        $set = new DimensionSet(['a' => 30, 'b' => 60]);
        $modified = $set->apply(['a' => 10]);

        $this->assertSame(40, $modified->get('a'));
        $this->assertSame(60, $modified->get('b'));
    }

    // ─── dominant ────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_dominant_returns_highest_dimension(): void
    {
        $set = new DimensionSet(['joy' => 40, 'anger' => 70, 'fear' => 55]);

        $this->assertSame('anger', $set->dominant());
    }

    /**
     * @return void
     */
    public function test_dominant_returns_null_when_all_at_zero(): void
    {
        $set = new DimensionSet(['a' => 0, 'b' => 0]);

        $this->assertNull($set->dominant());
    }

    /**
     * @return void
     */
    public function test_dominant_returns_null_on_empty_set(): void
    {
        $this->assertNull((new DimensionSet([]))->dominant());
    }

    /**
     * @return void
     */
    public function test_dominant_with_threshold_filters_below(): void
    {
        $set = new DimensionSet(['joy' => 40, 'anger' => 70]);

        // Both are above 0; with threshold=50 only anger qualifies
        $this->assertSame('anger', $set->dominant(50));
    }

    /**
     * @return void
     */
    public function test_dominant_with_threshold_returns_null_when_none_qualifies(): void
    {
        $set = new DimensionSet(['joy' => 30, 'anger' => 40]);

        $this->assertNull($set->dominant(50));
    }

    /**
     * @return void
     */
    public function test_dominant_threshold_must_be_exceeded_not_equalled(): void
    {
        $set = new DimensionSet(['exact' => 50]);

        // Value equals threshold — should NOT qualify
        $this->assertNull($set->dominant(50));
    }

    // ─── all ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_all_returns_full_map(): void
    {
        $set = new DimensionSet(['a' => 10, 'b' => 20]);

        $this->assertSame(['a' => 10, 'b' => 20], $set->all());
    }

    /**
     * @return void
     */
    public function test_all_returns_empty_array_for_empty_set(): void
    {
        $this->assertSame([], (new DimensionSet([]))->all());
    }
}
