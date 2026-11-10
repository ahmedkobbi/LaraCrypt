<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Unit;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Obfuscator;
use PHPUnit\Framework\TestCase;

final class ObfuscatorTest extends TestCase
{
    private const SAMPLE = <<<'PHP'
    <?php
    function greet(string $who): string {
        return "Hello, " . $who . "!";
    }
    echo greet("world");
    PHP;

    public function test_same_seed_is_reproducible(): void
    {
        $a = (new Obfuscator())->obfuscate(self::SAMPLE, new ObfuscationConfig(seed: 'fixed'));
        $b = (new Obfuscator())->obfuscate(self::SAMPLE, new ObfuscationConfig(seed: 'fixed'));

        self::assertSame($a->code, $b->code);
    }

    public function test_different_seed_is_polymorphic(): void
    {
        $a = (new Obfuscator())->obfuscate(self::SAMPLE, new ObfuscationConfig(seed: 'seed-a'));
        $b = (new Obfuscator())->obfuscate(self::SAMPLE, new ObfuscationConfig(seed: 'seed-b'));

        self::assertNotSame($a->code, $b->code, 'Different seeds should yield different output.');
    }

    public function test_invalid_php_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Obfuscator())->obfuscate('<?php function (', new ObfuscationConfig(seed: 's'));
    }

    public function test_string_encoding_can_be_disabled(): void
    {
        $config = new ObfuscationConfig(seed: 's', encodeStrings: false);
        $result = (new Obfuscator())->obfuscate(self::SAMPLE, $config);

        self::assertStringNotContainsString('Decoder::d', $result->code);
        self::assertNotContains('encode-strings', $result->appliedPasses);
        self::assertSame(0, $result->encodedStrings);
    }

    public function test_runtime_strings_are_encoded_but_not_counted_when_short(): void
    {
        $config = new ObfuscationConfig(seed: 's', minStringLen: 100);
        $result = (new Obfuscator())->obfuscate(self::SAMPLE, $config);

        // With a very high threshold nothing is encoded.
        self::assertSame(0, $result->encodedStrings);
        self::assertStringNotContainsString('Decoder::d', $result->code);
    }
}
