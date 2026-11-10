<?php

declare(strict_types=1);

namespace LaraCrypt\Engine;

use LaraCrypt\Engine\Config\ObfuscationConfig;
use LaraCrypt\Engine\Pass\EncodeStringLiteralsPass;
use LaraCrypt\Engine\Pass\PassInterface;
use LaraCrypt\Engine\Pass\StripCommentsPass;
use PhpParser\Error as ParserError;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as StandardPrinter;

/**
 * Entry point for the source-obfuscation pipeline (Layers 0–1).
 *
 * Pipeline: parse -> run enabled passes in order -> pretty-print.
 * The full reprint already discards original formatting; the passes add the
 * actual protection. Every pass is semantics-preserving and harness-gated.
 */
final class Obfuscator
{
    private readonly Parser $parser;

    private readonly StandardPrinter $printer;

    /** @var list<PassInterface> */
    private readonly array $passes;

    /**
     * @param list<PassInterface>|null $passes  Override the default pass order (tests/extension).
     */
    public function __construct(?array $passes = null)
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->printer = new StandardPrinter();

        $this->passes = $passes ?? [
            new StripCommentsPass(),
            new EncodeStringLiteralsPass(),
        ];
    }

    public function obfuscate(string $source, ObfuscationConfig $config): ObfuscationResult
    {
        $context = new ObfuscationContext($config);

        try {
            $ast = $this->parser->parse($source);
        } catch (ParserError $e) {
            throw new \InvalidArgumentException('Input is not valid PHP: ' . $e->getMessage(), 0, $e);
        }

        if ($ast === null) {
            throw new \InvalidArgumentException('Input is not valid PHP: empty parse result.');
        }

        $applied = [];

        foreach ($this->passes as $pass) {
            if (! $pass->enabled($context)) {
                continue;
            }

            $ast = $pass->run($ast, $context);
            $applied[] = $pass->name();
        }

        $code = $this->printer->prettyPrintFile($ast);

        return new ObfuscationResult(
            code: $code . "\n",
            nameMap: $context->nameMap(),
            appliedPasses: $applied,
            encodedStrings: $context->stringCount(),
        );
    }
}
