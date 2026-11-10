<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Unit;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Obfuscator;
use LaraCrypt\Engine\Pass\StripCommentsPass;
use PHPUnit\Framework\TestCase;

final class StripCommentsPassTest extends TestCase
{
    private const SAMPLE = <<<'PHP'
    <?php
    // a line comment
    # a hash comment
    /* a block comment */
    /** A docblock. */
    function f(): int {
        return 1; // trailing
    }
    PHP;

    private function obfuscate(ObfuscationConfig $config): string
    {
        return (new Obfuscator([new StripCommentsPass()]))->obfuscate(self::SAMPLE, $config)->code;
    }

    public function test_comments_are_removed_but_docblocks_kept_by_default(): void
    {
        $code = $this->obfuscate(new ObfuscationConfig(seed: 's'));

        self::assertStringNotContainsString('line comment', $code);
        self::assertStringNotContainsString('hash comment', $code);
        self::assertStringNotContainsString('block comment', $code);
        self::assertStringNotContainsString('trailing', $code);
        self::assertStringContainsString('A docblock.', $code, 'Docblocks are kept for reflection safety.');
    }

    public function test_docblocks_removed_when_opted_in(): void
    {
        $code = $this->obfuscate(new ObfuscationConfig(seed: 's', stripDocBlocks: true));

        self::assertStringNotContainsString('A docblock.', $code);
    }
}
