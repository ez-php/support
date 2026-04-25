<?php

declare(strict_types=1);

namespace EzPhp\Support;

/**
 * An immutable set of named numeric dimensions, each clamped to [0, 100].
 *
 * Applicable to personality systems, NPC mood, skill stats, faction reputation,
 * or any domain that tracks multiple named properties on a bounded scale.
 *
 * @package EzPhp\Support
 */
final class DimensionSet
{
    /** @var array<string, int> */
    private array $dimensions;

    /**
     * @param array<string, int> $dimensions Initial values. Each is clamped to [0, 100].
     */
    public function __construct(array $dimensions)
    {
        $this->dimensions = [];

        foreach ($dimensions as $name => $value) {
            $this->dimensions[$name] = max(0, min(100, $value));
        }
    }

    /**
     * Return a new instance with the given deltas applied, each result clamped to [0, 100].
     *
     * Dimensions not present in the current set are initialised to 0 before applying the delta.
     * Dimensions not mentioned in $deltas are carried over unchanged.
     *
     * @param array<string, int> $deltas
     *
     * @return self
     */
    public function apply(array $deltas): self
    {
        $new = $this->dimensions;

        foreach ($deltas as $name => $delta) {
            $current = $new[$name] ?? 0;
            $new[$name] = max(0, min(100, $current + $delta));
        }

        return new self($new);
    }

    /**
     * Return the name of the dimension with the highest value strictly above $threshold.
     *
     * Returns null when no dimension exceeds the threshold.
     * When multiple dimensions share the highest value, the first one encountered wins.
     *
     * @param int $threshold Minimum value a dimension must exceed to qualify.
     *
     * @return string|null
     */
    public function dominant(int $threshold = 0): ?string
    {
        $best = null;
        $bestValue = $threshold;

        foreach ($this->dimensions as $name => $value) {
            if ($value > $bestValue) {
                $bestValue = $value;
                $best = $name;
            }
        }

        return $best;
    }

    /**
     * Return the value of the named dimension, or 0 if it does not exist.
     *
     * @param string $name
     *
     * @return int
     */
    public function get(string $name): int
    {
        return $this->dimensions[$name] ?? 0;
    }

    /**
     * Return all dimensions as a name → value map.
     *
     * @return array<string, int>
     */
    public function all(): array
    {
        return $this->dimensions;
    }
}
