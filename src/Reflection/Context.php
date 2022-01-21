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

class Context
{
    public ?string $namespace;

    public array $constants = [];

    public array $functions = [];

    public array $classLikes = [];

    /**
     * @param string|null $namespace Namespace
     */
    public function __construct(?string $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @param string $name Fully qualified or unqualified name
     * @return string
     */
    public function qualifyName(string $name): string
    {
        if ($name[0] === '\\') {
            return substr($name, 1);
        }

        if (!$this->namespace) {
            return $name;
        }

        return $this->namespace . '\\' . $name;
    }

    /**
     * @param string $name ClassLike name
     * @return string
     */
    public function resolveConstant(string $name): string
    {
        return $this->resolve($this->constants, $name);
    }

    /**
     * @param string $name Function name
     * @return string
     */
    public function resolveFunction(string $name): string
    {
        return $this->resolve($this->functions, $name);
    }

    /**
     * @param string $name ClassLike name
     * @return string
     */
    public function resolveClassLike(string $name): string
    {
        return $this->resolve($this->classLikes, $name);
    }

    /**
     * @param array<string, string> $imports Imports
     * @param string $name Element name
     * @return string
     */
    protected function resolve(array $imports, string $name): string
    {
        if (!$name) {
            return $name;
        }

        if ($name[0] === '\\') {
            return substr($name, 1);
        }

        $first = strchr($name, '\\', true);
        if ($first === false) {
            $first = $name;
            $append = '';
        } else {
            $append = substr($name, strlen($first));
        }

        if (isset($imports[$first])) {
            return $imports[$first] . $append;
        }

        return $this->namespace ? ($this->namespace . '\\' . $name) : $name;
    }
}
