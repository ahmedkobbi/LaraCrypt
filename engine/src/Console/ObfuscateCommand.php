<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Console;

use LaraCrypt\Engine\Build\ProjectBuilder;
use LaraCrypt\Engine\Config\ObfuscationConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'obfuscate',
    description: 'Obfuscate a PHP file or project directory (source layer).',
)]
final class ObfuscateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'Path to a .php file or a project directory')
            ->addArgument('output', InputArgument::REQUIRED, 'Output directory for the protected build')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Deterministic build seed (default: random)')
            ->addOption('no-encode-strings', null, InputOption::VALUE_NONE, 'Disable string-literal encoding')
            ->addOption('strip-docblocks', null, InputOption::VALUE_NONE, 'Also strip docblocks (may break reflection)')
            ->addOption('keep-comments', null, InputOption::VALUE_NONE, 'Keep ordinary comments');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $inputPath = (string) $input->getArgument('input');
        $outputPath = (string) $input->getArgument('output');

        $seed = $input->getOption('seed');
        $seed = \is_string($seed) && $seed !== '' ? $seed : bin2hex(random_bytes(16));

        $config = new ObfuscationConfig(
            seed: $seed,
            stripComments: ! $input->getOption('keep-comments'),
            stripDocBlocks: (bool) $input->getOption('strip-docblocks'),
            encodeStrings: ! $input->getOption('no-encode-strings'),
        );

        $io->title('LaraCrypt — source obfuscation');

        try {
            $report = (new ProjectBuilder())->build($inputPath, $outputPath, $config);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d file(s) protected, %d skipped, %d string(s) encoded.',
            $report->fileCount(),
            $report->skippedCount(),
            $report->totalEncodedStrings(),
        ));

        $io->writeln('<info>Runtime written to</info> ' . rtrim($outputPath, '/') . '/laracrypt_runtime.php');
        $io->writeln('<comment>Require it once during bootstrap (or install the native loader for the hybrid tier).</comment>');

        return Command::SUCCESS;
    }
}
