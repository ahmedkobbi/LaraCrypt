<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Pass;

use LaraCrypt\Engine\ObfuscationContext;
use PhpParser\Node;

/**
 * A single semantics-preserving transformation over a parsed AST.
 *
 * CONTRACT: a pass MUST NOT change the observable behavior of the program.
 * Every concrete pass is gated by the semantic-equivalence harness before it ships.
 */
interface PassInterface
{
    /** Stable machine name, e.g. "strip-comments". */
    public function name(): string;

    /** True when the current config enables this pass. */
    public function enabled(ObfuscationContext $context): bool;

    /**
     * Transform the given statements in place and return the new statement list.
     *
     * @param array<int, Node\Stmt> $stmts
     * @return array<int, Node\Stmt>
     */
    public function run(array $stmts, ObfuscationContext $context): array;
}
