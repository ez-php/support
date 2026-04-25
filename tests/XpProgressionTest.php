<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\LevelUpResult;
use EzPhp\Support\XpProgression;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class XpProgressionTest
 *
 * @package Tests
 */
#[CoversClass(XpProgression::class)]
#[UsesClass(LevelUpResult::class)]
final class XpProgressionTest extends TestCase
{
    // ─── xpForLevel ──────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_xp_for_level_one_equals_base(): void
    {
        $prog = new XpProgression(base: 100, exponent: 1.5);

        $this->assertSame(100, $prog->xpForLevel(1));
    }

    /**
     * @return void
     */
    public function test_xp_for_level_zero_returns_zero(): void
    {
        $this->assertSame(0, (new XpProgression())->xpForLevel(0));
    }

    /**
     * @return void
     */
    public function test_xp_for_level_negative_returns_zero(): void
    {
        $this->assertSame(0, (new XpProgression())->xpForLevel(-5));
    }

    /**
     * @return void
     */
    public function test_xp_for_level_increases_with_level(): void
    {
        $prog = new XpProgression(base: 100, exponent: 1.5);

        $this->assertGreaterThan($prog->xpForLevel(1), $prog->xpForLevel(2));
        $this->assertGreaterThan($prog->xpForLevel(2), $prog->xpForLevel(3));
        $this->assertGreaterThan($prog->xpForLevel(9), $prog->xpForLevel(10));
    }

    /**
     * Linear exponent: xpForLevel(N) = base * N.
     *
     * @return void
     */
    public function test_xp_for_level_linear_exponent(): void
    {
        $prog = new XpProgression(base: 50, exponent: 1.0);

        $this->assertSame(50, $prog->xpForLevel(1));
        $this->assertSame(100, $prog->xpForLevel(2));
        $this->assertSame(150, $prog->xpForLevel(3));
    }

    // ─── applyXp ─────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_apply_xp_no_level_up(): void
    {
        $prog = new XpProgression(base: 100, exponent: 1.0);

        // Level 1 requires 100; gaining 50 from 0 should not level up
        $result = $prog->applyXp(0, 1, 50);

        $this->assertSame(50, $result->newXp());
        $this->assertSame(1, $result->newLevel());
        $this->assertSame(0, $result->levelUps());
        $this->assertFalse($result->didLevelUp());
    }

    /**
     * @return void
     */
    public function test_apply_xp_single_level_up(): void
    {
        // base=100, exponent=1.0 → level 2 requires 200 XP
        $prog = new XpProgression(base: 100, exponent: 1.0);

        $result = $prog->applyXp(150, 1, 60);

        $this->assertSame(210, $result->newXp());
        $this->assertSame(2, $result->newLevel());
        $this->assertSame(1, $result->levelUps());
        $this->assertTrue($result->didLevelUp());
    }

    /**
     * @return void
     */
    public function test_apply_xp_multiple_level_ups_in_one_call(): void
    {
        // base=100, exponent=1.0 → thresholds: L2=200, L3=300, L4=400
        $prog = new XpProgression(base: 100, exponent: 1.0);

        $result = $prog->applyXp(0, 1, 450);

        $this->assertSame(450, $result->newXp());
        $this->assertSame(4, $result->newLevel());
        $this->assertSame(3, $result->levelUps());
    }

    /**
     * XP is not reset or consumed on level-up — it accumulates.
     *
     * @return void
     */
    public function test_apply_xp_accumulates_across_levels(): void
    {
        $prog = new XpProgression(base: 100, exponent: 1.0);

        $result1 = $prog->applyXp(0, 1, 250);
        $result2 = $prog->applyXp($result1->newXp(), $result1->newLevel(), 100);

        $this->assertSame(350, $result2->newXp());
        $this->assertSame(3, $result2->newLevel());
    }

    /**
     * @return void
     */
    public function test_apply_xp_returns_level_up_result_instance(): void
    {
        $result = (new XpProgression())->applyXp(0, 1, 0);

        $this->assertInstanceOf(LevelUpResult::class, $result);
    }

    // ─── constructor validation ───────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_constructor_throws_on_zero_base(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new XpProgression(base: 0);
    }

    /**
     * @return void
     */
    public function test_constructor_throws_on_negative_base(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new XpProgression(base: -1);
    }

    /**
     * @return void
     */
    public function test_constructor_throws_on_zero_exponent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new XpProgression(exponent: 0.0);
    }

    /**
     * @return void
     */
    public function test_constructor_throws_on_negative_exponent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new XpProgression(exponent: -0.5);
    }
}
