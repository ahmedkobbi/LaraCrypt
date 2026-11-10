<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Unit;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Obfuscator;
use LaraCrypt\Engine\Pass\EncodeStringLiteralsPass;
use PHPUnit\Framework\TestCase;

final class EncodeStringLiteralsPassTest extends TestCase
{
    private function obfuscate(string $php): string
    {
        return (new Obfuscator([new EncodeStringLiteralsPass()]))
            ->obfuscate($php, new ObfuscationConfig(seed: 'pass-seed'))
            ->code;
    }

    public function test_runtime_string_is_encoded(): void
    {
        $code = $this->obfuscate('<?php $x = "runtime-value";');

        self::assertStringContainsString('Decoder::d(', $code);
        self::assertStringNotContainsString('runtime-value', $code);
    }

    /**
     * Strings in these positions MUST stay literal or the output would not compile.
     */
    public function test_constant_contexts_are_left_literal(): void
    {
        $php = <<<'PHP'
        <?php
        const G = "global-constant";
        enum E: string { case A = "enum-backing"; }
        #[\Attribute]
        class C {
            const K = "class-constant";
            public string $p = "property-default";
            public static string $s = "static-default";
            public function m(string $a = "param-default"): void {
                static $v = "static-var-default";
            }
        }
        PHP;

        $code = $this->obfuscate($php);

        foreach ([
            'global-constant',
            'enum-backing',
            'class-constant',
            'property-default',
            'static-default',
            'param-default',
            'static-var-default',
        ] as $literal) {
            self::assertStringContainsString(
                '"' . $literal . '"',
                $code,
                "Literal '{$literal}' in a constant context must not be encoded.",
            );
        }
    }

    public function test_output_still_compiles(): void
    {
        $php = <<<'PHP'
        <?php
        const G = "c";
        class C {
            const K = "k";
            public function m(string $a = "d"): string { return "run:" . $a; }
        }
        PHP;

        $code = $this->obfuscate($php);

        // php -l via token check: a parse must succeed.
        $tokens = @\PhpToken::tokenize($code);
        self::assertNotEmpty($tokens);
        self::assertNull(self::lintError($code), 'Obfuscated output must be syntactically valid.');
    }

    private static function lintError(string $code): ?string
    {
        $file = tempnam(sys_get_temp_dir(), 'lc-lint');
        self::assertIsString($file);
        file_put_contents($file, $code);

        $output = [];
        $exit = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exit);
        @unlink($file);

        return $exit === 0 ? null : implode("\n", $output);
    }
}
