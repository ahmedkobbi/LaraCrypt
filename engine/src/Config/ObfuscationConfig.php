<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Config;

/**
 * Immutable configuration for a single obfuscation build.
 *
 * Conservative defaults: every transformation that could plausibly interact
 * with runtime string-resolution (reflection, docblock annotations) is opt-in
 * or defaults to the safe choice. Correctness always wins over coverage.
 */
final class ObfuscationConfig
{
    /**
     * @param non-empty-string $seed    Deterministic build seed. Same seed + same
     *                                  input => byte-identical output (reproducible),
     *                                  while different seeds yield polymorphic output.
     * @param bool $stripComments       Remove `//`, `#` and `/* *\/` comments.
     * @param bool $stripDocBlocks      Remove `/** *\/` docblocks. Default FALSE because
     *                                  frameworks and libraries read them via reflection
     *                                  (validation, Doctrine, attributes-as-text, etc.).
     * @param bool $encodeStrings       Encode plain string literals behind a runtime
     *                                  decoder (skipping constant-expression contexts).
     * @param int<1, 64> $minStringLen  Only encode strings at least this many bytes long
     *                                  (encoding tiny strings bloats output for no gain).
     */
    public function __construct(
        public readonly string $seed,
        public readonly bool $stripComments = true,
        public readonly bool $stripDocBlocks = false,
        public readonly bool $encodeStrings = true,
        public readonly int $minStringLen = 2,
    ) {
        if ($seed === '') {
            throw new \InvalidArgumentException('Obfuscation seed must be a non-empty string.');
        }

        if ($minStringLen < 1) {
            throw new \InvalidArgumentException('minStringLen must be >= 1.');
        }
    }

    /**
     * A sensible, correctness-first default profile.
     *
     * @param non-empty-string|null $seed
     */
    public static function default(?string $seed = null): self
    {
        return new self($seed ?? bin2hex(random_bytes(16)));
    }
}
