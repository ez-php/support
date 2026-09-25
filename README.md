# ez-php/support

General-purpose utility classes for the ez-php ecosystem. Zero external dependencies — pure PHP.

## Classes

### Range

Immutable value object for a bounded integer range.

```php
$r = Range::of(0, 100);

$r->contains(50);    // true
$r->clamp(150);      // 100
$r->random();        // uniform draw in [0, 100]
$r->weightedLow();   // min-of-two-draws, skewed towards 0
```

### WeightedRandom

Stateless weighted random selection from a list of associative arrays.

```php
$items = [
    ['name' => 'common', 'weight' => 9],
    ['name' => 'rare',   'weight' => 1],
];

WeightedRandom::pick($items);               // picks one, respecting weights
WeightedRandom::pickN($items, 2);           // picks 2 without replacement
WeightedRandom::weightedLow(0, 100);        // min-of-two-draws
```

Custom weight key:

```php
WeightedRandom::pick($items, 'probability');
```

### TimeProbability

Exponential time-based probability curve: `P(t) = 1 − exp(−t / λ)`.

```php
// Deterministic: just the probability value
TimeProbability::probability(minutesSince: 5.0, lambda: 4.0);  // ~0.713

// Stochastic: roll the curve
TimeProbability::exponential(minutesSince: 5.0);  // true/false
```

Always returns `true` once `minutesSince >= hardCapMinutes` (default 15).

### DailyQuota

Immutable value object for "daily allowance + per-action growing cooldown + UTC midnight reset".

```php
$quota = new DailyQuota(dailyLimit: 5, cooldownBaseSeconds: 300, cooldownStepSeconds: 300);
$now   = new DateTimeImmutable();

if ($quota->canPerform($now)) {
    $quota = $quota->perform($now);
    // persist $quota state
}

$quota->remaining();      // actions left today
$quota->cooldownUntil();  // next available time
$quota->usedToday();      // actions performed today
```

UTC midnight resets happen automatically inside `canPerform()` and `perform()`. Call
`resetIfNeeded($now)` explicitly to get a fresh instance without performing an action.

### CronExpression

Minimal five-field cron matcher (`minute hour day-of-month month day-of-week`), used by
`ez-php/scheduler` and `ez-php/queue`.

```php
CronExpression::isDue('*/15 * * * *', new DateTimeImmutable()); // every 15 minutes
CronExpression::isDue('0 6 * * 1', $now);                       // Mondays at 06:00
```

Supports `*`, `N` and `*/N` per field; day-of-week is 0–6 with Sunday = 0. Ranges, lists
and names are not supported. A malformed expression (not exactly five fields) is never due.

## Installation

```bash
composer require ez-php/support
```

## Testing

```bash
composer full
```
