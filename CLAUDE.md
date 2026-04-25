# Coding Guidelines

Applies to the entire ez-php project — framework core, all modules, and the application template.

---

## Environment

- PHP **8.5**, Composer for dependency management
- All project based commands run **inside Docker** — never directly on the host

```
docker compose exec app <command>
```

Container name: `ez-php-app`, service name: `app`.

---

## Quality Suite

Run after every change:

```
docker compose exec app composer full
```

Executes in order:
1. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
2. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
3. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse   # PHPStan only
composer cs        # CS Fixer only
composer test      # PHPUnit only
```

**PHPStan:** never suppress with `@phpstan-ignore-line` — always fix the root cause.

---

## Coding Standards

- `declare(strict_types=1)` at the top of every PHP file
- Typed properties, parameters, and return values — avoid `mixed`
- PHPDoc on every class and public method
- One responsibility per class — keep classes small and focused
- Constructor injection — no service locator pattern
- No global state unless intentional and documented

**Naming:**

| Thing | Convention |
|---|---|
| Classes / Interfaces | `PascalCase` |
| Methods / variables | `camelCase` |
| Constants | `UPPER_CASE` |
| Files | Match class name exactly |

**Principles:** SOLID · KISS · DRY · YAGNI

---

## Workflow & Behavior

- Write tests **before or alongside** production code (test-first)
- Read and understand the relevant code before making any changes
- Modify the minimal number of files necessary
- Keep implementations small — if it feels big, it likely belongs in a separate module
- No hidden magic — everything must be explicit and traceable
- No large abstractions without clear necessity
- No heavy dependencies — check if PHP stdlib suffices first
- Respect module boundaries — don't reach across packages
- Keep the framework core small — what belongs in a module stays there
- Document architectural reasoning for non-obvious design decisions
- Do not change public APIs unless necessary
- Prefer composition over inheritance — no premature abstractions

---

## New Modules & CLAUDE.md Files

### 1 — Required files

Every module under `modules/<name>/` must have:

| File | Purpose |
|---|---|
| `composer.json` | package definition, deps, autoload |
| `phpstan.neon` | static analysis config, level 9 |
| `phpunit.xml` | test suite config |
| `.php-cs-fixer.php` | code style config |
| `.gitignore` | ignore `vendor/`, `.env`, cache |
| `.env.example` | environment variable defaults (copy to `.env` on first run) |
| `docker-compose.yml` | Docker Compose service definition (always `container_name: ez-php-<name>-app`) |
| `docker/app/Dockerfile` | module Docker image (`FROM au9500/php:8.5`) |
| `docker/app/container-start.sh` | container entrypoint: `composer install` → `sleep infinity` |
| `docker/app/php.ini` | PHP ini overrides (`memory_limit`, `display_errors`, `xdebug.mode`) |
| `.github/workflows/ci.yml` | standalone CI pipeline |
| `README.md` | public documentation |
| `tests/TestCase.php` | base test case for the module |
| `start.sh` | convenience script: copy `.env`, bring up Docker, wait for services, exec shell |
| `CLAUDE.md` | see section 2 below |

### 2 — CLAUDE.md structure

Every module `CLAUDE.md` must follow this exact structure:

1. **Full content of `CODING_GUIDELINES.md`, verbatim** — copy it as-is, do not summarize or shorten
2. A `---` separator
3. `# Package: ez-php/<name>` (or `# Directory: <name>` for non-package directories)
4. Module-specific section covering:
   - Source structure — file tree with one-line description per file
   - Key classes and their responsibilities
   - Design decisions and constraints
   - Testing approach and infrastructure requirements (MySQL, Redis, etc.)
   - What does **not** belong in this module

### 3 — Docker scaffold

Run from the new module root (requires `"ez-php/docker": "0.*"` in `require-dev`):

```
vendor/bin/docker-init
```

This copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the module, replacing `{{MODULE_NAME}}` placeholders. Existing files are never overwritten.

After scaffolding:

1. Adapt `docker-compose.yml` — add or remove services (MySQL, Redis) as needed
2. Adapt `.env.example` — fill in connection defaults matching the services above
3. Assign a unique host port for each exposed service (see table below)

**Allocated host ports:**

| Package | `DB_HOST_PORT` (MySQL) | `REDIS_PORT` |
|---|---|---|
| root (`ez-php-project`) | 3306 | 6379 |
| `ez-php/framework` | 3307 | — |
| `ez-php/orm` | 3309 | — |
| `ez-php/cache` | — | 6380 |
| **next free** | **3310** | **6381** |

Only set a port for services the module actually uses. Modules without external services need no port config.

### 4 — Monorepo scripts

`packages.sh` at the project root is the **central package registry**. Both `push_all.sh` and `update_all.sh` source it — the package list lives in exactly one place.

When adding a new module, add `"$ROOT/modules/<name>"` to the `PACKAGES` array in `packages.sh` in **alphabetical order** among the other `modules/*` entries (before `framework`, `ez-php`, and the root entry at the end).

---

# Package: ez-php/support

General-purpose utility classes. Zero external dependencies — pure PHP.

---

## Source Structure

```
src/
├── Range.php            — Immutable integer range: contains, clamp, random, weightedLow
├── WeightedRandom.php   — Weighted random selection: pick, pickN (no replacement), weightedLow
├── TimeProbability.php  — Exponential time-based probability curve with hard cap
└── DailyQuota.php       — Daily allowance + growing cooldown + UTC midnight reset (immutable)

tests/
├── TestCase.php             — Base PHPUnit test case
├── RangeTest.php            — Range unit tests
├── WeightedRandomTest.php   — WeightedRandom unit + statistical tests
├── TimeProbabilityTest.php  — TimeProbability deterministic + behavioural tests
└── DailyQuotaTest.php       — DailyQuota state machine tests with controlled time
```

---

## Key Classes and Responsibilities

### Range (`src/Range.php`)

Readonly value object wrapping `[min, max]` inclusive bounds.

| Method | Returns | Description |
|---|---|---|
| `of(min, max)` | `self` | Named constructor; throws when `min > max` |
| `min()` / `max()` | `int` | Bound accessors |
| `contains(value)` | `bool` | `min <= value <= max` |
| `clamp(value)` | `int` | `max(min, min(max, value))` |
| `random()` | `int` | Uniform draw via `random_int` |
| `weightedLow()` | `int` | `min(random_int, random_int)` — skewed towards lower end |

### WeightedRandom (`src/WeightedRandom.php`)

Stateless utility; private constructor; all methods static.

Items are `list<array<string, mixed>>` with a numeric weight under a configurable key (default `'weight'`).

| Method | Returns | Description |
|---|---|---|
| `pick(items, weightKey)` | `array\|null` | One weighted draw; null on empty |
| `pickN(items, n, weightKey)` | `list<array>` | N draws without replacement |
| `weightedLow(min, max)` | `int` | min-of-two-draws static variant |

### TimeProbability (`src/TimeProbability.php`)

Stateless utility; private constructor; all methods static.

Formula: `P(t) = 1 − exp(−t / λ)`. Always returns 1.0 when `t >= hardCapMinutes`.

| Method | Returns | Description |
|---|---|---|
| `probability(minutesSince, lambda, hardCapMinutes)` | `float` | Deterministic probability value |
| `exponential(minutesSince, lambda, hardCapMinutes)` | `bool` | Rolls the probability; always true at/beyond hard cap |

### DailyQuota (`src/DailyQuota.php`)

Immutable value object; state-changing methods return new instances.

Constructor parameters: `dailyLimit`, `cooldownBaseSeconds`, `cooldownStepSeconds`, `usedToday`, `cooldownUntil`, `lastReset`.

Cooldown formula after `n`-th action: `base + step × n` seconds.

| Method | Returns | Description |
|---|---|---|
| `resetIfNeeded(now)` | `self` | Resets counter/cooldown when UTC day changed; identity otherwise |
| `canPerform(now)` | `bool` | True when not over limit and cooldown expired (auto-resets internally) |
| `perform(now)` | `self` | Auto-resets + increments + schedules next cooldown |
| `remaining()` | `int` | `max(0, dailyLimit − usedToday)` |
| `usedToday()` | `int` | Raw counter |
| `cooldownUntil()` | `?DateTimeImmutable` | Next available time |
| `lastReset()` | `?DateTimeImmutable` | UTC midnight of last reset day |

---

## Design Decisions and Constraints

- **Immutable value objects** — All classes are `final`. State changes return new instances. Callers own persistence.
- **No framework coupling** — Zero dependencies; usable standalone or with any framework.
- **`WeightedRandom` uses float accumulation** — Handles fractional weights (e.g. `0.5`). The `$last` fallback handles floating-point precision edge cases where the cumulative sum falls marginally short of total.
- **`TimeProbability::probability` is separated from the roll** — The deterministic `probability()` method is fully testable; `exponential()` composes it with a single `mt_rand` roll.
- **`DailyQuota` resets by UTC day comparison** — `resetIfNeeded` compares UTC midnight timestamps so two instances created on different machines in different local timezones agree on reset boundaries.
- **`DailyQuota::perform` auto-resets** — Calling `perform(tomorrow)` on an exhausted quota correctly resets and records the first action of the new day without an explicit `resetIfNeeded` call.

---

## Testing Approach

- **Pure unit tests** — No database, no Redis, no network. All tests run on the host.
- **Controlled time** — `DailyQuota` tests pass `DateTimeImmutable` explicitly; no `date()` or `time()` calls in production code.
- **Statistical tests** — `WeightedRandom` and `Range::weightedLow` include sanity checks over 1000 draws. These are designed with generous bounds (e.g. mean < 45 on [0,100]) to avoid flakiness in CI.
- **Deterministic coverage of random branches** — `TimeProbability::exponential(0.0)` is always false (P=0); `exponential(hardCap)` is always true (P=1). These edges are tested without needing seed control.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| Persistent quota storage (DB/Redis) | Application layer |
| Rate limiting over HTTP requests | `ez-php/rate-limiter` |
| Arbitrary-precision arithmetic | `ez-php/bignum` |
| Monetary value objects | `ez-php/money` |
| Date/time formatting or parsing | Application layer / PHP stdlib |
| Framework service providers | Application layer |
