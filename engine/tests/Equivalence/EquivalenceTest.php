<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Equivalence;

use LaraCrypt\Engine\Tests\Support\EquivalenceHarness;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The hard correctness gate: for every fixture program, the obfuscated build must
 * produce byte-identical output and the same exit code as the original, under
 * multiple build seeds (polymorphism must never change behavior).
 */
final class EquivalenceTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function fixtures(): iterable
    {
        $dir = \dirname(__DIR__) . '/Fixtures/equivalence';

        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            foreach (['seed-one', 'another-seed-xyz', 'third-9'] as $seed) {
                yield $name . ' @ ' . $seed => [$file, $seed];
            }
        }
    }

    #[DataProvider('fixtures')]
    public function test_obfuscated_build_is_behaviorally_identical(string $file, string $seed): void
    {
        $source = file_get_contents($file);
        self::assertIsString($source);

        $harness = new EquivalenceHarness();
        $outcome = $harness->compare($source, $seed);

        $original = $outcome['original'];
        $obfuscated = $outcome['obfuscated'];

        // The original fixture must itself run cleanly (guards against bad fixtures).
        self::assertSame(0, $original->exitCode, "Original fixture errored:\n" . $original->stderr);

        // The obfuscation must have actually transformed something.
        self::assertNotEmpty($outcome['applied'], 'No passes were applied.');

        // Core guarantee: identical observable behavior.
        self::assertSame(
            $original->stdout,
            $obfuscated->stdout,
            "stdout diverged for {$file} (seed {$seed}).\nObfuscated stderr:\n" . $obfuscated->stderr,
        );

        self::assertSame(
            $original->exitCode,
            $obfuscated->exitCode,
            "exit code diverged for {$file} (seed {$seed}).",
        );

        self::assertSame('', $obfuscated->stderr, "Obfuscated build emitted errors for {$file} (seed {$seed}).");
    }
}
