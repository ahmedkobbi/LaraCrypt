<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Build;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Obfuscator;
use LaraCrypt\Engine\Runtime\RuntimeEmitter;
use LaraCrypt\Engine\Support\BuildKey;

/**
 * Obfuscates a single file or a directory tree of PHP sources into an output
 * directory, emits the runtime stub once, and writes a build manifest that
 * carries the aggregated name-map (the basis for future stack-trace symbolication).
 */
final class ProjectBuilder
{
    /**
     * Directories never descended into: dependencies, VCS, and framework caches.
     *
     * @var list<string>
     */
    private const EXCLUDED_DIRS = ['vendor', 'node_modules', '.git', '.github', 'storage'];

    public function __construct(
        private readonly Obfuscator $obfuscator = new Obfuscator(),
        private readonly RuntimeEmitter $runtimeEmitter = new RuntimeEmitter(),
    ) {
    }

    public function build(string $inputPath, string $outputPath, ObfuscationConfig $config): BuildReport
    {
        $input = realpath($inputPath);

        if ($input === false) {
            throw new \InvalidArgumentException("Input path does not exist: {$inputPath}");
        }

        if (! is_dir($outputPath) && ! mkdir($outputPath, 0755, true) && ! is_dir($outputPath)) {
            throw new \RuntimeException("Could not create output directory: {$outputPath}");
        }

        $output = realpath($outputPath);

        if ($output === false) {
            throw new \RuntimeException("Could not resolve output directory: {$outputPath}");
        }

        $report = new BuildReport();
        $nameMap = [];

        if (is_file($input)) {
            $this->obfuscateFile($input, basename($input), $output, $config, $report, $nameMap);
        } else {
            foreach ($this->phpFiles($input, $output) as $file => $relative) {
                $this->obfuscateFile($file, $relative, $output, $config, $report, $nameMap);
            }
        }

        // Emit the shared runtime and the build manifest once per build.
        file_put_contents(
            $output . '/laracrypt_runtime.php',
            $this->runtimeEmitter->emit(new BuildKey($config->seed)),
        );

        file_put_contents(
            $output . '/laracrypt-build.json',
            json_encode([
                'engine' => 'laracrypt/engine',
                'seedHash' => hash('sha256', $config->seed),
                'files' => $report->fileCount(),
                'encodedStrings' => $report->totalEncodedStrings(),
                'nameMap' => $nameMap,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        return $report;
    }

    /**
     * @param array<string, array<string, string>> $nameMap
     */
    private function obfuscateFile(
        string $absolute,
        string $relative,
        string $output,
        ObfuscationConfig $config,
        BuildReport $report,
        array &$nameMap,
    ): void {
        $source = file_get_contents($absolute);

        if ($source === false) {
            $report->skip();

            return;
        }

        try {
            $result = $this->obfuscator->obfuscate($source, $config);
        } catch (\InvalidArgumentException) {
            // Not valid PHP (or an unsupported construct): copy through untouched
            // rather than corrupt the project. Correctness over coverage.
            $report->skip();
            $this->writeFile($output . '/' . $relative, $source);

            return;
        }

        $this->writeFile($output . '/' . $relative, $result->code);
        $report->addFile($relative, $output . '/' . $relative, $result->encodedStrings, $result->appliedPasses);

        if ($result->nameMap !== []) {
            $nameMap[$relative] = $result->nameMap;
        }
    }

    private function writeFile(string $path, string $contents): void
    {
        $dir = \dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Could not create directory: {$dir}");
        }

        file_put_contents($path, $contents);
    }

    /**
     * @return iterable<string, string> absolute path => path relative to $root
     */
    private function phpFiles(string $root, string $output): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $current) use ($output): bool {
                    if ($current->isDir()) {
                        // Never descend into excluded dirs or the output directory itself.
                        if (\in_array($current->getFilename(), self::EXCLUDED_DIRS, true)) {
                            return false;
                        }

                        return realpath($current->getPathname()) !== $output;
                    }

                    return strtolower($current->getExtension()) === 'php';
                },
            ),
        );

        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            $absolute = $file->getPathname();
            $relative = ltrim(str_replace($root, '', $absolute), \DIRECTORY_SEPARATOR);
            yield $absolute => $relative;
        }
    }
}
