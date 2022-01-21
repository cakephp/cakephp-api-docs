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

class LoadedNamespace
{
    public ?string $name;

    public ?LoadedNamespace $parent;

    public array $children = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedConstant>
     */
    public array $constants = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedFunction>
     */
    public array $functions = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedInterface>
     */
    public array $interfaces = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedTrait>
     */
    public array $traits = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedClass>
     */
    public array $classes = [];

    /**
     * @param string|null $namespace Namespace name
     * @param \Cake\ApiDocs\Reflection\LoadedNamespace|null $parent Parent namespace
     */
    public function __construct(?string $namespace, ?LoadedNamespace $parent)
    {
        $this->name = $namespace ?? 'Global';
        if ($namespace) {
            $endPos = strrpos($namespace, '\\');
            if ($endPos !== false) {
                $this->name = substr($namespace, $endPos + 1);
            }
        }
        $this->parent = $parent;
    }

    /**
     * @return string|null
     */
    public function namespaced(): ?string
    {
        if ($this->parent) {
            return $this->parent->namespaced() . '\\' . $this->name;
        }

        return $this->name;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedNode $node Node to add
     * @return void
     */
    public function add(LoadedNode $node): void
    {
        if ($node instanceof LoadedConstant) {
            $this->constants[$node->name] = $node;
            ksort($this->constants);
        } elseif ($node instanceof LoadedFunction) {
            $this->functions[$node->name] = $node;
            ksort($this->functions);
        } elseif ($node instanceof LoadedInterface) {
            $this->interfaces[$node->name] = $node;
            ksort($this->interfaces);
        } elseif ($node instanceof LoadedTrait) {
            $this->traits[$node->name] = $node;
            ksort($this->traits);
        } elseif ($node instanceof LoadedClass) {
            $this->classes[$node->name] = $node;
            ksort($this->classes);
        }
    }

    /**
     * Finds loaded namespace starting with this node.
     *
     * @param string $namespace Namespace name
     * @return self
     */
    public function find(string $namespace): LoadedNamespace
    {
        if ($namespace === $this->namespaced()) {
            return $this;
        }

        $parts = explode('\\', substr($namespace, strlen($this->namespaced()) + 1));

        $name = $this->namespaced();
        $loaded = $this;
        while ($parts) {
            $name .= '\\' . array_shift($parts);
            if (isset($loaded->children[$name])) {
                $loaded = $loaded->children[$name];
            } else {
                $loaded = $loaded->children[$name] = new LoadedNamespace($name, $loaded);
            }
        }

        return $loaded;
    }

    /**
     * Checks whether name is a member of namespace, child namespace or grandchild namespace.
     *
     * @param string|null $name Qualified name
     * @return bool
     */
    public function isRelated(?string $name): bool
    {
        if ($name === null) {
            return $this->namespaced() === null;
        }

        if ($this->namespaced() === null) {
            return !str_contains($name, '\\');
        }

        return str_starts_with($name, $this->namespaced());
    }
}
