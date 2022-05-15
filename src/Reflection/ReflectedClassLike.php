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

class ReflectedClassLike extends ReflectedNode
{
    public array $uses = [];

    public array $constants = [];

    public array $properties = [];

    public array $methods = [];

    /**
     * Returns qualified name.
     *
     * @return string
     */
    public function qualifiedName(): string
    {
        return $this->context->namespace ? "{$this->context->namespace}\\{$this->name}" : $this->name;
    }
}
