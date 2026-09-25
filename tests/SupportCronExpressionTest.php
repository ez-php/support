<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use EzPhp\Support\CronExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CronExpression::class)]
final class SupportCronExpressionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function cases(): iterable
    {
        // 2026-09-27 is a Sunday (day-of-week 0).
        yield 'every minute' => ['* * * * *', '2026-09-27 13:37', true];
        yield 'exact minute+hour hit' => ['37 13 * * *', '2026-09-27 13:37', true];
        yield 'exact minute miss' => ['36 13 * * *', '2026-09-27 13:37', false];
        yield 'step minutes hit' => ['*/15 * * * *', '2026-09-27 13:45', true];
        yield 'step minutes miss' => ['*/15 * * * *', '2026-09-27 13:46', false];
        yield 'step zero never matches' => ['*/0 * * * *', '2026-09-27 13:00', false];
        yield 'day of month' => ['0 0 27 * *', '2026-09-27 00:00', true];
        yield 'month' => ['0 0 * 10 *', '2026-09-27 00:00', false];
        yield 'sunday is 0' => ['0 0 * * 0', '2026-09-27 00:00', true];
        yield 'monday' => ['0 0 * * 1', '2026-09-28 00:00', true];
        yield 'leading zero minute' => ['05 * * * *', '2026-09-27 13:05', true];
        yield 'too few fields' => ['* * * *', '2026-09-27 13:37', false];
        yield 'too many fields' => ['* * * * * *', '2026-09-27 13:37', false];
        yield 'double space is malformed' => ['*  * * * *', '2026-09-27 13:37', false];
    }

    #[DataProvider('cases')]
    public function testIsDue(string $expression, string $time, bool $expected): void
    {
        self::assertSame($expected, CronExpression::isDue($expression, new DateTimeImmutable($time)));
    }
}
