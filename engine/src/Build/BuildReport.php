<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Build;

/**
 * Human- and machine-readable summary of a project build.
 */
final class BuildReport
{
    /** @var list<array{path: string, passes: list<string>, encodedStrings: int}> */
    private array $files = [];

    private int $skipped = 0;

    public function addFile(string $relativePath, string $outputPath, int $encodedStrings, array $passes): void
    {
        $this->files[] = [
            'path' => $relativePath,
            'passes' => array_values($passes),
            'encodedStrings' => $encodedStrings,
        ];
    }

    public function skip(): void
    {
        $this->skipped++;
    }

    public function fileCount(): int
    {
        return \count($this->files);
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }

    public function totalEncodedStrings(): int
    {
        return array_sum(array_column($this->files, 'encodedStrings'));
    }

    /**
     * @return list<array{path: string, passes: list<string>, encodedStrings: int}>
     */
    public function files(): array
    {
        return $this->files;
    }
}
