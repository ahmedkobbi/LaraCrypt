<?php

declare(strict_types=1);

namespace LaraCrypt\Engine;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Support\BuildKey;

/**
 * Mutable per-build state shared across passes: the config, the derived build key,
 * a seeded RNG (for polymorphism), and the name-map that will later power
 * stack-trace symbolication (original <-> obfuscated symbol recovery).
 */
final class ObfuscationContext
{
    public readonly BuildKey $key;

    private readonly \Random\Randomizer $rng;

    /**
     * original symbol => obfuscated symbol, grouped by kind ("var", "string", ...).
     *
     * @var array<string, array<string, string>>
     */
    private array $nameMap = [];

    private int $stringCount = 0;

    public function __construct(public readonly ObfuscationConfig $config)
    {
        $this->key = new BuildKey($config->seed);

        // Deterministic RNG seeded from the build seed: reproducible yet unique per seed.
        $seedInt = unpack('P', substr(hash('sha256', $config->seed, true), 0, 8));
        $engine = new \Random\Engine\Mt19937($seedInt === false ? 0 : (int) $seedInt[1]);
        $this->rng = new \Random\Randomizer($engine);
    }

    public function randomizer(): \Random\Randomizer
    {
        return $this->rng;
    }

    public function recordString(): void
    {
        $this->stringCount++;
    }

    public function stringCount(): int
    {
        return $this->stringCount;
    }

    public function mapName(string $kind, string $original, string $obfuscated): void
    {
        $this->nameMap[$kind][$original] = $obfuscated;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function nameMap(): array
    {
        return $this->nameMap;
    }
}
