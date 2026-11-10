<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Support;

/**
 * Captured result of running a PHP program in a subprocess.
 */
final class ExecResult
{
    public function __construct(
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly int $exitCode,
    ) {
    }
}
