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
 * Supported per field: `*` (any), `N` (exact value) and `*\/N` (every N steps
 * from 0). Day-of-week uses 0–6 with Sunday = 0. Ranges (`1-5`), lists (`1,15`)
 * and names (`MON`) are not supported and never match.
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

        return self::matchField($fields[0], (int) $time->format('i'))
            && self::matchField($fields[1], (int) $time->format('G'))
            && self::matchField($fields[2], (int) $time->format('j'))
            && self::matchField($fields[3], (int) $time->format('n'))
            && self::matchField($fields[4], (int) $time->format('w'));
    }

    /**
     * Match a single cron field against the current calendar value.
     *
     * @param string $field Cron field value.
     * @param int    $value Current calendar value.
     *
     * @return bool
     */
    private static function matchField(string $field, int $value): bool
    {
        if ($field === '*') {
            return true;
        }

        if (str_starts_with($field, '*/')) {
            $n = (int) substr($field, 2);

            return $n > 0 && $value % $n === 0;
        }

        return (int) $field === $value;
    }
}
