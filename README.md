# LaraCrypt

Advanced PHP/Laravel code **obfuscation & protection** SaaS (B2B + B2C).

LaraCrypt protects enterprise PHP/Laravel codebases with a layered,
defense-in-depth pipeline: AST source obfuscation → encrypted bytecode → a
native Zend loader extension, wrapped in a cloud platform with licensing,
builds, and billing.

> **Honest stance:** runnable PHP can always be reverse-engineered with enough
> effort. LaraCrypt maximizes *cost-to-break* and enforces licensing; it does not
> claim to be "unbreakable." See the master plan for the full rationale.

## Repository layout

| Path | What it is |
| --- | --- |
| [`docs/LARACRYPT_MASTER_PLAN.md`](docs/LARACRYPT_MASTER_PLAN.md) | The approved master prompt, architecture, and phased roadmap |
| [`engine/`](engine/) | The obfuscation engine (AST pipeline, CLI, tests) — Layers 0–1 |

## Status

Early build. The **source-obfuscation engine** is implemented and fully tested
(unit + semantic-equivalence harness across multiple PHP constructs and seeds).
Next up per the plan: scope-aware renaming, control-flow flattening, then the
Layer 2/3 encrypted-bytecode + native loader, and the Laravel SaaS platform
(Laravel 13 · PHP 8.4 · MySQL 8.4 · Laravel Cashier).

## Quick start (engine)

```bash
cd engine
composer install
php bin/laracrypt <input-dir> <output-dir> --seed=my-seed
vendor/bin/phpunit
```

See [`engine/README.md`](engine/README.md) for details.
