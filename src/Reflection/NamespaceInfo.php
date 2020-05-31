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

namespace Cake\ApiDocs\Reflection;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Namespace_;

/**
 * NamespaceInfo
 */
class NamespaceInfo
{
    /**
     * @var string
     */
    protected $fqsen;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \phpDocumentor\Reflection\Php\Namespace_;
     */
    protected $namespace;

    /**
     * @var \phpDocumentor\Reflection\Php\Namespace_|null
     */
    protected $parent;

    /**
     * @var \phpDocumentor\Reflection\Php\Namespace_[]
     */
    protected $children;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Namespace_ $namespace reflection namespace
     * @param \phpDocumentor\Reflection\Php\Namespace_|null $parent parent fqsen
     * @param string[] $children children fqsens
     */
    public function __construct(string $fqsen, Namespace_ $namespace, ?Namespace_ $parent, array $children)
    {
        $this->fqsen = $fqsen;
        $this->name = (new Fqsen($fqsen))->getName();
        $this->namespace = $namespace;
        $this->parent = $parent;
        $this->children = $children;
    }

    /**
     * @return string
     */
    public function getFqsen(): string
    {
        return $this->fqsen;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \phpDocumentor\Reflection\Php\Namespace_
     */
    public function getNamespace(): Namespace_
    {
        return $this->namespace;
    }

    /**
     * @return \phpDocumentor\Reflection\Php\Namespace_|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * @return \phpDocumentor\Reflection\Php\Namespace_[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
