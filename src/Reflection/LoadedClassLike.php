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

class LoadedClassLike
{
    /**
     * @var string
     */
    public string $fqsen;

    /**
     * @var string
     */
    public string $namespace;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedFile
     */
    public LoadedFile $loadedFile;

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedClass|null>
     */
    public array $extends = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedClass|null>
     */
    public array $interfaces = [];

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedClass>
     */
    public array $traits = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedConstant[]
     */
    public array $constants = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedProperty[]
     */
    public array $properties = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedMethod[]
     */
    public array $methods = [];

    /**
     * @param string $fqsen fqsen
     * @param \Cake\ApiDocs\Reflection\LoadedFile $loadedFile Loaded file
     */
    public function __construct(string $fqsen, LoadedFile $loadedFile)
    {
        $this->fqsen = $fqsen;
        $this->namespace = substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
        $this->loadedFile = $loadedFile;
    }

    /**
     * @param string $fqsen Interface fqsen
     * @param \Cake\ApiDocs\Reflection\LoadedInterface|null $other Loaded interface
     * @return void
     */
    public function addInterface(string $fqsen, ?LoadedInterface $other): void
    {
        $this->interfaces[$fqsen] = $other;
        if ($other) {
            $this->interfaces = array_merge($this->interfaces, $other->interfaces);
            foreach ($other->constants as $name => $constant) {
                $this->constants[$name] = clone $constant;
                $this->constants[$name]->owner = $this->fqsen;
            }
            foreach ($other->methods as $name => $method) {
                if (isset($this->methods[$name])) {
                    $this->methods[$name]->declarations += $method->declarations;
                } else {
                    $this->methods[$name] = clone $method;
                    $this->methods[$name]->owner = $this->fqsen;
                }
            }
        }
    }

    /**
     * @param string $fqsen Class fqsen
     * @param \Cake\ApiDocs\Reflection\LoadedClass|null $other Loaded class
     * @return void
     */
    public function addExtends(string $fqsen, ?LoadedClass $other): void
    {
        $this->extends[$fqsen] = $other;
        if ($other) {
            $this->interfaces = array_merge($this->interfaces, $other->interfaces);
            $this->constants = array_merge($this->constants, $other->constants);
            foreach ($other->properties as $name => $property) {
                $this->properties[$name] = clone $property;
                $this->properties[$name]->owner = $this->fqsen;
            }
            foreach ($other->methods as $name => $method) {
                if (isset($this->methods[$name])) {
                    $this->methods[$name]->declarations += $method->declarations;
                } else {
                    $this->methods[$name] = clone $method;
                    $this->methods[$name]->owner = $this->fqsen;
                }
            }
        }
    }

    /**
     * @param string $fqsen Trait fqsen
     * @param \Cake\ApiDocs\Reflection\LoadedTrait $other Loaded trait
     * @return void
     */
    public function addTrait(string $fqsen, LoadedTrait $other): void
    {
        $this->traits[$fqsen] = $other;
        foreach ($other->properties as $name => $property) {
            $this->properties[$name] = clone $property;
            $this->properties[$name]->owner = $this->fqsen;
        }
        foreach ($other->methods as $name => $method) {
            if (isset($this->methods[$name])) {
                $this->methods[$name]->declarations += $method->declarations;
            } else {
                $this->methods[$name] = clone $method;
                $this->methods[$name]->owner = $this->fqsen;
            }
        }
    }
}
