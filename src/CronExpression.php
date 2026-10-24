<?php

declare(strict_types=1);

namespace EzPhp\Support;

use DateTimeInterface;

/**
 * Class CronExpression
 *
 * Minimal five-field cron matcher ("minute hour day-of-month month day-of-week")
 * shared by ez-php/scheduler and ez-php/queue, which previously kept identical,
 * hand-synced copies.
 *
 * Supported per field: `*` (any), `N` (exact value), `A-B` (inclusive range),
 * `*\/N` (every N steps from 0), `A-B/N` (every N steps from A up to B) and
 * comma-separated lists of those (`1,15,30`, `1-5,10`). Day-of-week uses 0–6
 * with Sunday = 0. Names (`MON`), macros (`@daily`) and `L`/`W`/`#` are not
 * supported and never match.
 *
 * Day-of-month and day-of-week combine as in standard cron: when both are
 * restricted (neither starts with `*`), a day matches if *either* does —
 * `0 9 1,15 * 1-5` runs on the 1st, the 15th and every weekday. When one of
 * them starts with `*` (including `*\/N`), both must match.
 *
 * A malformed expression (not exactly five space-separated fields) is never
 * due — callers fail closed rather than throwing.
 *
 * @package EzPhp\Support
 */
final class CronExpression
{
    /**
     * Whether the expression is due at the given moment (minute resolution).
     *
     * @param string            $expression Cron expression: "minute hour dom month dow".
     * @param DateTimeInterface $time       The moment to evaluate.
     *
     * @return bool
     */
    public static function isDue(string $expression, DateTimeInterface $time): bool
    {
        $fields = explode(' ', $expression);

        if (count($fields) !== 5) {
            return false;
        }

        if (!self::matchField($fields[0], (int) $time->format('i'))
            || !self::matchField($fields[1], (int) $time->format('G'))
            || !self::matchField($fields[3], (int) $time->format('n'))
        ) {
            return false;
        }

        $dayOfMonth = self::matchField($fields[2], (int) $time->format('j'));
        $dayOfWeek = self::matchField($fields[4], (int) $time->format('w'));

        // Standard (Vixie) cron: when both day fields are restricted, either may match.
        if (!str_starts_with($fields[2], '*') && !str_starts_with($fields[4], '*')) {
            return $dayOfMonth || $dayOfWeek;
        }

        return $dayOfMonth && $dayOfWeek;
    }

    /**
     * Match a single cron field (a comma-separated list) against the current calendar value.
     *
     * @param string $field Cron field value.
     * @param int    $value Current calendar value.
     *
     * @return bool
     */
    private static function matchField(string $field, int $value): bool
    {
        // A malformed item anywhere makes the whole field fail closed, not just that item.
        if (preg_match('~^(\*|\d+(-\d+)?)(/\d+)?(,(\*|\d+(-\d+)?)(/\d+)?)*$~', $field) !== 1) {
            return false;
        }

        foreach (explode(',', $field) as $item) {
            if (self::matchItem($item, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match one list item: `*`, `N`, `A-B`, `*\/S` or `A-B/S`. A malformed item never matches.
     *
     * @param string $item  One list item.
     * @param int    $value Current calendar value.
     *
     * @return bool
     */
    private static function matchItem(string $item, int $value): bool
    {
        $step = 1;

        if (str_contains($item, '/')) {
            [$item, $stepPart] = explode('/', $item, 2);

            if (!ctype_digit($stepPart) || (int) $stepPart === 0) {
                return false;
            }

            $step = (int) $stepPart;
        }

        if ($item === '*') {
            return $value % $step === 0;
        }

        $bounds = explode('-', $item);

        if (count($bounds) > 2 || !ctype_digit($bounds[0]) || !ctype_digit($bounds[1] ?? $bounds[0])) {
            return false;
        }

        $from = (int) $bounds[0];

        if (count($bounds) === 1) {
            return $step === 1 && $value === $from;
        }

        $to = (int) $bounds[1];

        return $value >= $from && $value <= $to && ($value - $from) % $step === 0;
    }
}
