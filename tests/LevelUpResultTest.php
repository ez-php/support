<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Support\LevelUpResult;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class LevelUpResultTest
 *
 * @package Tests
 */
#[CoversClass(LevelUpResult::class)]
final class LevelUpResultTest extends TestCase
{
    /**
     * @return void
     */
    public function test_getters(): void
    {
        $result = new LevelUpResult(350, 4, 2);

        $this->assertSame(350, $result->newXp());
        $this->assertSame(4, $result->newLevel());
        $this->assertSame(2, $result->levelUps());
    }

    /**
     * @return void
     */
    public function test_did_level_up_true_when_level_ups_positive(): void
    {
        $this->assertTrue((new LevelUpResult(100, 2, 1))->didLevelUp());
    }

    /**
     * @return void
     */
    public function test_did_level_up_false_when_no_level_up(): void
    {
        $this->assertFalse((new LevelUpResult(50, 1, 0))->didLevelUp());
    }
}
