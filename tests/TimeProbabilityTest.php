<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\TimeProbability;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TimeProbabilityTest
 *
 * @package Tests
 */
#[CoversClass(TimeProbability::class)]
final class TimeProbabilityTest extends TestCase
{
    // ─── probability ─────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_probability_is_zero_at_t_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, TimeProbability::probability(0.0), 0.0001);
    }

    /**
     * @return void
     */
    public function test_probability_is_one_at_hard_cap(): void
    {
        $this->assertSame(1.0, TimeProbability::probability(15.0, hardCapMinutes: 15));
    }

    /**
     * @return void
     */
    public function test_probability_is_one_beyond_hard_cap(): void
    {
        $this->assertSame(1.0, TimeProbability::probability(20.0, hardCapMinutes: 15));
    }

    /**
     * @return void
     */
    public function test_probability_increases_with_time(): void
    {
        $p1 = TimeProbability::probability(1.0);
        $p5 = TimeProbability::probability(5.0);
        $p10 = TimeProbability::probability(10.0);

        $this->assertGreaterThan($p1, $p5);
        $this->assertGreaterThan($p5, $p10 - 0.0001); // p10 > p5
        $this->assertGreaterThan($p5, $p10);
    }

    /**
     * Verify the formula at λ=4, t=4: P = 1 − e^(−1) ≈ 0.6321.
     *
     * @return void
     */
    public function test_probability_formula_at_lambda(): void
    {
        $p = TimeProbability::probability(4.0, lambda: 4.0, hardCapMinutes: 60);
        $this->assertEqualsWithDelta(1.0 - exp(-1.0), $p, 0.0001);
    }

    /**
     * @return void
     */
    public function test_probability_is_between_zero_and_one(): void
    {
        foreach ([0.5, 1.0, 3.0, 7.0, 14.9] as $t) {
            $p = TimeProbability::probability($t);
            $this->assertGreaterThanOrEqual(0.0, $p);
            $this->assertLessThanOrEqual(1.0, $p);
        }
    }

    /**
     * @return void
     */
    public function test_probability_throws_on_zero_lambda(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeProbability::probability(5.0, lambda: 0.0);
    }

    /**
     * @return void
     */
    public function test_probability_throws_on_negative_lambda(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeProbability::probability(5.0, lambda: -1.0);
    }

    // ─── exponential ─────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_exponential_always_false_at_zero_minutes(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertFalse(TimeProbability::exponential(0.0));
        }
    }

    /**
     * @return void
     */
    public function test_exponential_always_true_at_hard_cap(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->assertTrue(TimeProbability::exponential(15.0, hardCapMinutes: 15));
        }
    }

    /**
     * @return void
     */
    public function test_exponential_always_true_beyond_hard_cap(): void
    {
        $this->assertTrue(TimeProbability::exponential(100.0, hardCapMinutes: 15));
    }

    /**
     * @return void
     */
    public function test_exponential_negative_time_treated_as_zero(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->assertFalse(TimeProbability::exponential(-1.0));
        }
    }
}
