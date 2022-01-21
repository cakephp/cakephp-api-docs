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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ApiDocs\Reflection;

class ReflectedNamespace
{
    public string $name;

    public ?string $qualifiedName;

    public ?self $parent;

    /**
     * @var array<string, self>
     */
    public array $children = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\ReflectedDefine>
     */
    public array $defines = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\ReflectedFunction>
     */
    public array $functions = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\ReflectedInterface>
     */
    public array $interfaces = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\ClassTref>
     */
    public array $classes = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\ReflectedTrait>
     */
    public array $traits = [];

    /**
     * @param string|null $qualifiedName Qualified name
     * @param self|null $parent Parent namespace
     */
    public function __construct(?string $qualifiedName, ?self $parent)
    {
        preg_match('/[^\\\\]+$/', $qualifiedName ?? 'Global', $matches);
        $this->name = $matches[0];
        $this->qualifiedName = $qualifiedName;
        $this->parent = $parent;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedNode $ref Ref to add
     * @return void
     */
    public function addNode(ReflectedNode $ref): void
    {
        if ($ref instanceof ReflectedDefine) {
            $this->constants[$ref->name] = $ref;
            ksort($this->constants);
        } elseif ($ref instanceof ReflectedFunction) {
            $this->functions[$ref->name] = $ref;
            ksort($this->functions);
        } elseif ($ref instanceof ReflectedInterface) {
            $this->interfaces[$ref->name] = $ref;
            ksort($this->interfaces);
        } elseif ($ref instanceof ReflectedClass) {
            $this->classes[$ref->name] = $ref;
            ksort($this->classes);
        } elseif ($ref instanceof ReflectedTrait) {
            $this->traits[$ref->name] = $ref;
            ksort($this->traits);
        }
    }

    /**
     * Finds loaded namespace starting with this node.
     *
     * @param string|null $namespace Namespace name
     * @return self|null
     */
    public function find(?string $namespace): ?self
    {
        if ($namespace === $this->name) {
            return $this;
        }

        if ($namespace === null || $this->name === null) {
            return null;
        }

        if (!str_starts_with($namespace, $this->name)) {
            null;
        }

        $parts = explode('\\', substr($namespace, strlen($this->name) + 1));

        $ref = $this;
        $qualifiedName = $this->name;
        while ($parts) {
            $name = array_shift($parts);
            $qualifiedName .= '\\' . $name;
            if (isset($ref->children[$qualifiedName])) {
                $ref = $ref->children[$qualifiedName];
            } else {
                $ref = $ref->children[$qualifiedName] = new self($qualifiedName, $ref);
            }
        }

        return $ref;
    }

    /**
     * Checks if the passed in namespace is an ancestor.
     *
     * @param \Cake\ApiDocs\Reflection\ReflectedNamespace $namespace Reflected namespace
     * @return bool
     */
    public function hasAncestor(ReflectedNamespace $namespace): bool
    {
        if ($this->parent === $namespace) {
            return true;
        }

        if ($this->parent !== null) {
            return $this->parent->hasAncestor($namespace);
        }

        return false;
    }
}
