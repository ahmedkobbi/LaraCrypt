<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Runtime;

use LaraCrypt\Engine\Support\BuildKey;

/**
 * Emits the standalone runtime stub that obfuscated code depends on.
 *
 * In the SaaS "hybrid-max" product this logic is absorbed by the native loader
 * (the key never lands on readable disk). For the pure source-obfuscation tier
 * the runtime is shipped as a small PHP file required once during bootstrap,
 * exactly like a third-party loader stub.
 */
final class RuntimeEmitter
{
    public function emit(BuildKey $key): string
    {
        $hex = $key->hex();

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace LaraCrypt\\Runtime;

        /**
         * LaraCrypt source-layer runtime decoder (generated — do not edit).
         * Build key is bound to this build; a different build cannot decode it.
         */
        if (! \\class_exists(Decoder::class, false)) {
            final class Decoder
            {
                private const K = '{$hex}';

                public static function d(string \$payload): string
                {
                    \$raw = \\base64_decode(\$payload, true);

                    if (\$raw === false) {
                        throw new \\RuntimeException('Corrupt LaraCrypt string payload.');
                    }

                    \$key = (string) \\hex2bin(self::K);
                    \$out = '';
                    \$klen = \\strlen(\$key);

                    for (\$i = 0, \$n = \\strlen(\$raw); \$i < \$n; \$i++) {
                        \$out .= \$raw[\$i] ^ \$key[\$i % \$klen];
                    }

                    return \$out;
                }
            }
        }

        PHP;
    }
}
