# LaraCrypt Engine

The source-obfuscation core of [LaraCrypt](../docs/LARACRYPT_MASTER_PLAN.md) —
an AST-based, **semantics-preserving** PHP code protector (Layers 0–1 of the
master plan's protection pipeline).

> This package is the pure-PHP "source obfuscation" tier. The hybrid-max product
> adds Layer 2 (encrypted bytecode) and Layer 3 (a native Zend loader extension)
> on top. See the [master plan](../docs/LARACRYPT_MASTER_PLAN.md).

## What it does today

| Pass | Effect | Safety |
| --- | --- | --- |
| `strip-comments` | Removes comments; keeps docblocks by default | Docblocks kept for reflection safety |
| `encode-strings` | Rewrites runtime string literals to `\LaraCrypt\Runtime\Decoder::d(...)` | **Skips every compile-time-constant context** |

Builds are **reproducible** (same seed ⇒ identical output) yet **polymorphic**
(different seed ⇒ different output), via a seed-derived build key.

## Correctness guarantee

Every transformation is gated by a **semantic-equivalence harness**
(`tests/Equivalence`): each fixture program is executed in both original and
obfuscated form, under multiple seeds, and the stdout + exit code must match
byte-for-byte. A pass that changes observable behavior is treated as a
highest-severity bug and does not ship.

The string encoder refuses to touch literals inside contexts PHP requires to be
compile-time constants (class/global const, enum cases, property/param/static-var
defaults, attributes, `declare()`), because a function call there would be a
fatal error.

## Usage (CLI)

```bash
composer install
php bin/laracrypt <input-file-or-dir> <output-dir> --seed=my-build-seed
```

The output directory gets the protected files, a `laracrypt_runtime.php` stub
(require it once during bootstrap — or install the native loader in the hybrid
tier), and a `laracrypt-build.json` manifest containing the name-map used for
future stack-trace symbolication.

## Usage (library)

```php
use LaraCrypt\Engine\Obfuscator;
use LaraCrypt\Engine\Config\ObfuscationConfig;

$result = (new Obfuscator())->obfuscate($phpSource, new ObfuscationConfig(seed: 'build-1'));
echo $result->code;            // protected PHP
echo $result->encodedStrings;  // how many literals were encoded
```

## Development

```bash
composer install
vendor/bin/phpunit      # unit + semantic-equivalence suites
vendor/bin/pint --test  # code style (PSR-12)
```

## Roadmap (next passes)

Scope-aware local-variable renaming, control-flow flattening with opaque
predicates, mixed-boolean-arithmetic expression rewriting, and the Layer 2/3
encrypted-bytecode + native loader — each added behind the same equivalence gate.
See the [master plan](../docs/LARACRYPT_MASTER_PLAN.md).

## Status & honesty

This is the source-obfuscation tier: output is still runnable PHP and therefore
reversible with effort. It raises cost-to-break; it is not "unbreakable." Strong
confidentiality comes from the native loader tier (Layer 3).
