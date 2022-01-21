<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ApiDocs;

use Cake\ApiDocs\Reflection\Context;
use Cake\ApiDocs\Reflection\LoadedClass;
use Cake\ApiDocs\Reflection\LoadedConstant;
use Cake\ApiDocs\Reflection\LoadedFunction;
use Cake\ApiDocs\Reflection\LoadedInterface;
use Cake\ApiDocs\Reflection\LoadedTrait;
use Cake\ApiDocs\Reflection\Source;
use PhpParser\Node;
use PhpParser\Node\Const_ as NodeConst_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class FileVisitor extends NodeVisitorAbstract
{
    protected string $path;

    protected Context $context;

    /**
     * @var array
     */
    protected array $nodes = [];

    /**
     * @param string $path File path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->context = new Context(null);
    }

    /**
     * @return array
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->context = new Context((string)$node->name ?: null);

            return null;
        }

        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                switch ($node->type) {
                    case Use_::TYPE_NORMAL:
                        $this->context->classLikes += $this->normalizeUse($use);
                        break;
                    case Use_::TYPE_FUNCTION:
                        $this->context->functions += $this->normalizeUse($use);
                        break;
                    case Use_::TYPE_CONSTANT:
                        $this->context->constants += $this->normalizeUse($use);
                        break;
                }
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof GroupUse) {
            $prefix = (string)$node->prefix;
            foreach ($node->uses as $use) {
                switch ($use->type) {
                    case Use_::TYPE_NORMAL:
                        $this->context->classLikes += $this->normalizeUse($use, $prefix);
                        break;
                    case Use_::TYPE_FUNCTION:
                        $this->context->functions += $this->normalizeUse($use, $prefix);
                        break;
                    case Use_::TYPE_CONSTANT:
                        $this->context->constants += $this->normalizeUse($use, $prefix);
                        break;
                }
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof FuncCall && (string)$node->name === 'define') {
            if (count($node->args) === 2 && $node->args[0]->value instanceof String_) {
                $this->nodes[] = new LoadedConstant(
                    new NodeConst_((string)$node->args[0]->value->value, $node->args[1]->value, $node->getAttributes()),
                    new Source($this->path, $node->getStartLine(), $node->getEndLine()),
                    $this->context
                );
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Const_) {
            foreach ($node->consts as $const) {
                $this->nodes[] = new LoadedConstant(
                    $const,
                    new Source($this->path, $node->getStartLine(), $node->getEndLine()),
                    $this->context
                );
            }

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Function_) {
            $this->nodes[] = new LoadedFunction(
                $node,
                new Source($this->path, $node->getStartLine(), $node->getEndLine()),
                $this->context
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Interface_) {
            $this->nodes[] = new LoadedInterface(
                $node,
                new Source($this->path, $node->getStartLine(), $node->getEndLine()),
                $this->context
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Trait_) {
            $this->nodes[] = new LoadedTrait(
                $node,
                new Source($this->path, $node->getStartLine(), $node->getEndLine()),
                $this->context
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Class_) {
            $this->nodes[] = new LoadedClass(
                $node,
                new Source($this->path, $node->getStartLine(), $node->getEndLine()),
                $this->context
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->context = new Context(null);
        }

        return null;
    }

    /**
     * @param \PhpParser\Node\Stmt\UseUse $use Use node
     * @param string|null $prefix Group use prefix
     * @return array<string, string>
     */
    protected function normalizeUse(UseUse $use, ?string $prefix = null): array
    {
        $name = (string)$use->name;
        if ($prefix) {
            $name = $prefix . '\\' . $name;
        }

        $alias = $use->alias;
        if (!$alias) {
            $last = strrpos($name, '\\', -1);
            if ($last !== false) {
                $alias = substr($name, strrpos($name, '\\', -1) + 1);
            } else {
                $alias = $name;
            }
        }

        return [(string)$alias => $name];
    }

    /**
     * @param string $name Node name
     * @return string
     */
    protected function addNamespace(string $name): string
    {
        if ($this->context->namespace) {
            return $this->context->namespace . '\\' . $name;
        }

        return $name;
    }
}
