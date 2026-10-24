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
        yield 'range hit (weekday)' => ['0 9 * * 1-5', '2026-09-28 09:00', true];
        yield 'range miss (sunday)' => ['0 9 * * 1-5', '2026-09-27 09:00', false];
        yield 'range inclusive upper bound' => ['0 8-18 * * *', '2026-09-27 18:00', true];
        yield 'range below lower bound' => ['0 8-18 * * *', '2026-09-27 07:00', false];
        yield 'list hit' => ['1,15,30 * * * *', '2026-09-27 13:15', true];
        yield 'list miss' => ['1,15,30 * * * *', '2026-09-27 13:16', false];
        yield 'stepped range hit' => ['0 8-18/2 * * *', '2026-09-27 10:00', true];
        yield 'stepped range counts from lower bound' => ['0 9-18/2 * * *', '2026-09-27 11:00', true];
        yield 'stepped range off-step miss' => ['0 8-18/2 * * *', '2026-09-27 09:00', false];
        yield 'stepped range above upper bound' => ['0 8-18/2 * * *', '2026-09-27 20:00', false];
        yield 'list of ranges and values' => ['0 1-3,12,20-22 * * *', '2026-09-27 21:00', true];
        yield 'list with step item' => ['0,*/20 * * * *', '2026-09-27 13:40', true];
        yield 'reversed range never matches' => ['0 18-8 * * *', '2026-09-27 20:00', false];
        yield 'stepped range zero step never matches' => ['0 8-18/0 * * *', '2026-09-27 08:00', false];
        // Both day fields restricted: either may match (standard cron); otherwise both must.
        yield 'restricted dom or dow: dow hit' => ['0 9 1,15 * 1-5', '2026-09-28 09:00', true];
        yield 'restricted dom or dow: dom hit on a sunday' => ['0 9 1,15 * 1-5', '2026-11-01 09:00', true];
        yield 'restricted dom or dow: neither' => ['0 9 1,15 * 1-5', '2026-09-27 09:00', false];
        yield 'single dom and dow: dom hit' => ['0 0 27 * 1', '2026-09-27 00:00', true];
        yield 'starred dom step keeps and' => ['0 0 */2 * 1', '2026-10-05 00:00', false];
        yield 'starred dom step and dow both hit' => ['0 0 */2 * 1', '2026-09-28 00:00', true];
        yield 'starred dow step keeps and' => ['0 0 27 * */2', '2026-09-28 00:00', false];
        yield 'or still needs minute, hour and month' => ['0 9 1,15 10 1-5', '2026-09-28 09:00', false];
        yield 'empty list item is malformed' => ['1,,2 * * * *', '2026-09-27 13:01', false];
        yield 'non-numeric value is malformed' => ['5x * * * *', '2026-09-27 13:05', false];
        yield 'names are not supported' => ['0 0 * * MON', '2026-09-28 00:00', false];
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
