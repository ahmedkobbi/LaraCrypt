<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Tests\Support;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Obfuscator;
use LaraCrypt\Engine\Runtime\RuntimeEmitter;
use LaraCrypt\Engine\Support\BuildKey;

/**
 * The semantic-equivalence harness (plan Spike B / Part 4 gate).
 *
 * It obfuscates a self-contained PHP program, executes BOTH the original and the
 * obfuscated build in separate subprocesses, and reports their stdout+stderr and
 * exit codes so a test can assert they are byte-identical. Any divergence is a
 * correctness defect of the highest severity.
 */
final class EquivalenceHarness
{
    /**
     * @return array{original: ExecResult, obfuscated: ExecResult, applied: list<string>, encoded: int}
     */
    public function compare(string $source, string $seed = 'test-seed'): array
    {
        $config = new ObfuscationConfig(seed: $seed);
        $result = (new Obfuscator())->obfuscate($source, $config);

        $dir = $this->makeTempDir();

        try {
            $originalFile = $dir . '/original.php';
            $obfuscatedFile = $dir . '/obfuscated.php';
            $runtimeFile = $dir . '/runtime.php';

            file_put_contents($originalFile, $source);
            file_put_contents($obfuscatedFile, $result->code);
            file_put_contents($runtimeFile, (new RuntimeEmitter())->emit(new BuildKey($config->seed)));

            $original = $this->run([$originalFile]);
            $obfuscated = $this->run(['-d', 'auto_prepend_file=' . $runtimeFile, $obfuscatedFile]);
        } finally {
            $this->removeDir($dir);
        }

        return [
            'original' => $original,
            'obfuscated' => $obfuscated,
            'applied' => $result->appliedPasses,
            'encoded' => $result->encodedStrings,
        ];
    }

    /**
     * @param list<string> $args
     */
    private function run(array $args): ExecResult
    {
        $cmd = array_merge([PHP_BINARY, '-d', 'error_reporting=E_ALL', '-d', 'display_errors=1'], $args);

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes);

        if (! \is_resource($process)) {
            throw new \RuntimeException('Failed to start PHP subprocess.');
        }

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return new ExecResult($stdout, $stderr, $exit);
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/laracrypt-eq-' . bin2hex(random_bytes(6));
        if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Could not create temp dir.');
        }

        return $dir;
    }

    private function removeDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
