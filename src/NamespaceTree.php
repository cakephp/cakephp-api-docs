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

use phpDocumentor\Reflection\Php\Namespace_;

/**
 * NamespaceInfo
 */
class NamespaceTree
{
    /**
     * @var \phpDocumentor\Reflection\Php\Namespace_;
     */
    protected $namespace;

    /**
     * @var \Cake\ApiDocs\Util\NamespaceTree|null
     */
    protected $parent;

    /**
     * @var \Cake\ApiDocs\Util\NamespaceTree[]
     */
    protected $children = [];

    /**
     * @param \Cake\ApiDocs\Util\SourceLoader $sourceLoader source loader
     * @param \phpDocumentor\Reflection\Php\Namespace_ $namespace reflection namespace
     */
    public function __construct(SourceLoader $sourceLoader, Namespace_ $namespace)
    {
        $this->namespace = $namespace;

        $fqsen = (string)$namespace->getFqsen();
        $prevDelimiter = strrpos($fqsen, '\\');
        if ($prevDelimiter === false || $prevDelimiter === 0) {
            $parent = $sourceLoader->getNamespaces()[substr($fqsen, 0, $prevDelimiter)] ?? null;
            if ($parent) {
                $this->parent = new NamespaceInfo($sourceLoader, $parent);
            }
        }

        $children = [];
        $quotedFqsen = preg_quote($fqsen);
        $children = array_filter($sourceLoader->getNamespaces(), function ($namespace) use ($quotedFqsen) {
            return preg_match('/^' . $quotedFqsen . '\\\\[^\\\\]+$/', (string)$namespace->getFqsen()) === 1;
        });

        foreach ($children as $child) {
            $this->children[(string)$child->getFqsen()] = new NamespaceTree($sourceLoader, $child);
        }
        ksort($this->children);
    }

    /**
     * @return \phpDocumentor\Reflection\Php\Namespace_
     */
    public function getNamespace(): Namespace_
    {
        return $this->namespace;
    }

    /**
     * @return \Cake\ApiDocs\Util\NamespaceTree|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * @return \Cake\ApiDocs\Util\NamespaceTree[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
