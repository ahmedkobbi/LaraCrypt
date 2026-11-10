<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Pass;

use LaraCrypt\Engine\ObfuscationContext;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Removes comments from the AST.
 *
 * Doc comments are KEPT by default (config->stripDocBlocks = false) because
 * reflection-driven frameworks read them at runtime; stripping them is opt-in.
 */
final class StripCommentsPass implements PassInterface
{
    public function name(): string
    {
        return 'strip-comments';
    }

    public function enabled(ObfuscationContext $context): bool
    {
        return $context->config->stripComments || $context->config->stripDocBlocks;
    }

    public function run(array $stmts, ObfuscationContext $context): array
    {
        $config = $context->config;

        $visitor = new class ($config->stripComments, $config->stripDocBlocks) extends NodeVisitorAbstract {
            public function __construct(
                private readonly bool $stripComments,
                private readonly bool $stripDocBlocks,
            ) {
            }

            public function enterNode(Node $node): null
            {
                $comments = $node->getComments();

                if ($comments === []) {
                    return null;
                }

                $kept = [];

                foreach ($comments as $comment) {
                    $isDoc = $comment instanceof Comment\Doc;

                    if ($isDoc && $this->stripDocBlocks) {
                        continue;
                    }

                    if (! $isDoc && $this->stripComments) {
                        continue;
                    }

                    $kept[] = $comment;
                }

                $node->setAttribute('comments', $kept);

                return null;
            }
        };

        $traverser = new NodeTraverser($visitor);

        return $traverser->traverse($stmts);
    }
}
