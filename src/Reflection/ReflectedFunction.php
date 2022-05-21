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

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class ReflectedFunction extends ReflectedNode
{
    /**
     * @var array<string, \Cake\ApiDocs\Reflection\ReflectedParam>
     */
    public array $params = [];

    public ?TypeNode $returnType = null;

    public ?TypeNode $nativeReturnType = null;

    public bool $abstract = false;

    public bool $static = false;

    /**
     * @return void
     */
    public function __clone(): void
    {
        parent::__clone();
        foreach ($this->params as &$param) {
            $param = clone $param;
        }
        if ($this->returnType) {
            $this->returnType = clone $this->returnType;
        }
        if ($this->nativeReturnType) {
            $this->nativeReturnType = clone $this->nativeReturnType;
        }
    }
}
