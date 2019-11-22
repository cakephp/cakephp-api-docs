<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use Tree\Node\Node;
use Tree\Visitor\PreOrderVisitor;

class NamespaceTreeContext
{
    /**
     * @var \Tree\Node\Node
     */
    protected $root;

    /**
     * @var \App\Twig\Context\NamespaceContext[]
     */
    protected $contexts;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param \Tree\Node\Node $tree The namespace tree
     */
    public function __construct(Loader $loader, Node $tree)
    {
        $this->root = $tree;

        $contexts = array_map(function ($node) use ($loader) {
            $fqsen = $node->getValue();
            $element = $loader->getNamespaces()[$fqsen] ?? null;
            $children = array_map(function ($child) {
                return $child->getValue();
            }, $node->getChildren());

            return new NamespaceContext($loader, $fqsen, $element, $children);
        }, $this->root->accept(new PreOrderVisitor()));

        usort($contexts, function ($first, $second) {
            return strcmp($first->getFqsen(), $second->getFqsen());
        });

        $this->contexts = $contexts;
    }

    /**
     * @return \Tree\Node\Node
     */
    public function getRoot(): Node
    {
        return $this->root;
    }

    /**
     * @return \App\Twig\Context\NamespaceContext[]
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }
}
