<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use EzPhp\Support\DailyQuota;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class DailyQuotaTest
 *
 * @package Tests
 */
#[CoversClass(DailyQuota::class)]
final class DailyQuotaTest extends TestCase
{
    private DateTimeImmutable $now;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-06-15 10:00:00 UTC');
    }

    private function quota(int $limit = 5, int $base = 300, int $step = 300): DailyQuota
    {
        return new DailyQuota($limit, $base, $step);
    }

    // ─── Constructor validation ───────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_zero_daily_limit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DailyQuota(0, 300, 300);
    }

    /**
     * @return void
     */
    public function test_negative_cooldown_base_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DailyQuota(5, -1, 300);
    }

    /**
     * @return void
     */
    public function test_negative_cooldown_step_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DailyQuota(5, 300, -1);
    }

    // ─── Initial state ────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_can_perform_initially(): void
    {
        $this->assertTrue($this->quota()->canPerform($this->now));
    }

    /**
     * @return void
     */
    public function test_remaining_equals_daily_limit_initially(): void
    {
        $this->assertSame(5, $this->quota()->remaining());
    }

    /**
     * @return void
     */
    public function test_used_today_is_zero_initially(): void
    {
        $this->assertSame(0, $this->quota()->usedToday());
    }

    /**
     * @return void
     */
    public function test_cooldown_until_is_null_initially(): void
    {
        $this->assertNull($this->quota()->cooldownUntil());
    }

    // ─── perform ─────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_perform_increments_used_today(): void
    {
        $q = $this->quota()->perform($this->now);
        $this->assertSame(1, $q->usedToday());
    }

    /**
     * @return void
     */
    public function test_perform_decrements_remaining(): void
    {
        $q = $this->quota()->perform($this->now);
        $this->assertSame(4, $q->remaining());
    }

    /**
     * @return void
     */
    public function test_perform_sets_cooldown(): void
    {
        $q = $this->quota(5, 300, 300)->perform($this->now);
        // After 1st perform: cooldown = base + step * 1 = 600s
        $expected = $this->now->modify('+600 seconds');
        $this->assertNotNull($q->cooldownUntil());
        $this->assertEquals($expected, $q->cooldownUntil());
    }

    /**
     * @return void
     */
    public function test_perform_growing_cooldown(): void
    {
        $q = $this->quota(5, 300, 300)
            ->perform($this->now)       // cooldown = 300 + 300*1 = 600s
            ->perform($this->now->modify('+700 seconds'));  // cooldown = 300 + 300*2 = 900s

        $expected = $this->now->modify('+700 seconds')->modify('+900 seconds');
        $this->assertEquals($expected, $q->cooldownUntil());
    }

    // ─── canPerform ──────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_can_not_perform_during_cooldown(): void
    {
        $q = $this->quota()->perform($this->now);
        // Immediately after perform, still in cooldown
        $this->assertFalse($q->canPerform($this->now));
    }

    /**
     * @return void
     */
    public function test_can_perform_after_cooldown_expires(): void
    {
        $q = $this->quota(5, 300, 300)->perform($this->now);
        // cooldown = 300 + 300*1 = 600s → available at now+600
        $future = $this->now->modify('+601 seconds');
        $this->assertTrue($q->canPerform($future));
    }

    /**
     * @return void
     */
    public function test_can_not_perform_when_daily_limit_reached(): void
    {
        $q = $this->quota(2, 0, 0);
        $now = $this->now;
        $q = $q->perform($now)->perform($now);
        $this->assertFalse($q->canPerform($now));
    }

    /**
     * @return void
     */
    public function test_zero_cooldown_allows_immediate_reuse(): void
    {
        $q = new DailyQuota(5, 0, 0);
        $q = $q->perform($this->now);
        $this->assertTrue($q->canPerform($this->now));
    }

    // ─── resetIfNeeded ────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_reset_if_needed_resets_on_new_day(): void
    {
        $q = $this->quota()->perform($this->now)->perform($this->now);
        $this->assertSame(2, $q->usedToday());

        $tomorrow = $this->now->modify('+1 day');
        $reset = $q->resetIfNeeded($tomorrow);

        $this->assertSame(0, $reset->usedToday());
        $this->assertNull($reset->cooldownUntil());
    }

    /**
     * @return void
     */
    public function test_reset_if_needed_is_no_op_same_day(): void
    {
        $q = $this->quota()->perform($this->now);
        $same = $q->resetIfNeeded($this->now);
        $this->assertSame($q, $same);
    }

    /**
     * @return void
     */
    public function test_reset_if_needed_fires_on_first_call(): void
    {
        $q = $this->quota(); // lastReset = null
        $reset = $q->resetIfNeeded($this->now);
        $this->assertNotNull($reset->lastReset());
        $this->assertSame(0, $reset->usedToday());
    }

    /**
     * @return void
     */
    public function test_can_perform_auto_resets_on_new_day(): void
    {
        $q = $this->quota(2, 0, 0)
            ->perform($this->now)
            ->perform($this->now);
        $this->assertFalse($q->canPerform($this->now));

        $tomorrow = $this->now->modify('+1 day');
        $this->assertTrue($q->canPerform($tomorrow));
    }

    /**
     * @return void
     */
    public function test_perform_auto_resets_on_new_day(): void
    {
        $q = $this->quota(2, 0, 0)
            ->perform($this->now)
            ->perform($this->now);
        $this->assertSame(2, $q->usedToday());

        $tomorrow = $this->now->modify('+1 day');
        $q = $q->perform($tomorrow);
        $this->assertSame(1, $q->usedToday());
    }
}
