<?php

declare(strict_types=1);

namespace LaraCrypt\Engine;

/**
 * The outcome of obfuscating one PHP source string.
 */
final class ObfuscationResult
{
    /**
     * @param array<string, array<string, string>> $nameMap  For future symbolication.
     * @param list<string> $appliedPasses
     */
    public function __construct(
        public readonly string $code,
        public readonly array $nameMap,
        public readonly array $appliedPasses,
        public readonly int $encodedStrings,
    ) {
    }
}
