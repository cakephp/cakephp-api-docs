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

namespace Cake\ApiDocs;

use Cake\ApiDocs\Reflection\ReflectedClass;
use Cake\ApiDocs\Reflection\ReflectedDefine;
use Cake\ApiDocs\Reflection\ReflectedFunction;
use Cake\ApiDocs\Reflection\ReflectedInterface;
use Cake\ApiDocs\Reflection\ReflectedNode;
use Cake\ApiDocs\Reflection\ReflectedTrait;
use InvalidArgumentException;

class ProjectNamespace
{
    public string $name;

    public ?string $qualifiedName;

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
     * @param string $name Display name
     * @param string|null $qualifiedName Qualified name
     */
    public function __construct(string $name, ?string $qualifiedName)
    {
        $this->name = $name;
        $this->qualifiedName = $qualifiedName;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->children) &&
            empty($this->constants) &&
            empty($this->functions) &&
            empty($this->interfaces) &&
            empty($this->classes) &&
            empty($this->traits);
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
        } else {
            throw new InvalidArgumentException(sprintf('Cannot add node of type %s.', get_class($ref)));
        }
    }
}
