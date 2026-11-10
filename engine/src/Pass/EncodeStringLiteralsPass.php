<?php

declare(strict_types=1);

namespace LaraCrypt\Engine\Pass;

use LaraCrypt\Engine\ObfuscationContext;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Replaces plain string literals with a call to the LaraCrypt runtime decoder:
 *
 *     "secret"  =>  \LaraCrypt\Runtime\Decoder::d("<encoded>")
 *
 * CORRECTNESS: a function/method call is NOT a constant expression, so it is a
 * fatal error to place one where PHP requires a compile-time constant (class
 * const values, property/param defaults, enum cases, attributes, declare()).
 * This pass tracks those contexts and refuses to encode inside them. Only plain
 * `Scalar\String_` nodes are touched — interpolated strings are a different node
 * type and keep their runtime value untouched.
 */
final class EncodeStringLiteralsPass implements PassInterface
{
    /**
     * Node types whose ENTIRE subtree must remain a compile-time constant
     * expression. Encoding anything inside them would not compile.
     *
     * @var list<class-string<Node>>
     */
    private const CONSTANT_CONTEXT = [
        Node\Stmt\ClassConst::class,
        Node\Stmt\Const_::class,
        Node\Stmt\Property::class,
        Node\Stmt\EnumCase::class,
        Node\Stmt\Declare_::class,
        Node\Param::class,
        Node\StaticVar::class,
        Node\Attribute::class,
        Node\AttributeGroup::class,
    ];

    public function name(): string
    {
        return 'encode-strings';
    }

    public function enabled(ObfuscationContext $context): bool
    {
        return $context->config->encodeStrings;
    }

    public function run(array $stmts, ObfuscationContext $context): array
    {
        $visitor = new class ($context, self::CONSTANT_CONTEXT) extends NodeVisitorAbstract {
            private int $constDepth = 0;

            /**
             * @param list<class-string<Node>> $constantContext
             */
            public function __construct(
                private readonly ObfuscationContext $context,
                private readonly array $constantContext,
            ) {
            }

            public function enterNode(Node $node): null
            {
                foreach ($this->constantContext as $type) {
                    if ($node instanceof $type) {
                        $this->constDepth++;
                        break;
                    }
                }

                return null;
            }

            public function leaveNode(Node $node): Node|null
            {
                // Pair the decrement with the enter increment first.
                foreach ($this->constantContext as $type) {
                    if ($node instanceof $type) {
                        $this->constDepth--;

                        return null;
                    }
                }

                if (! $node instanceof Node\Scalar\String_) {
                    return null;
                }

                if ($this->constDepth > 0) {
                    return null; // inside a constant-expression context: must stay literal.
                }

                $value = $node->value;

                if (strlen($value) < $this->context->config->minStringLen) {
                    return null;
                }

                $this->context->recordString();

                $encoded = $this->context->key->encode($value);

                return new Node\Expr\StaticCall(
                    new Node\Name\FullyQualified('LaraCrypt\\Runtime\\Decoder'),
                    new Node\Identifier('d'),
                    [new Node\Arg(new Node\Scalar\String_($encoded))],
                );
            }
        };

        $traverser = new NodeTraverser($visitor);

        return $traverser->traverse($stmts);
    }
}
