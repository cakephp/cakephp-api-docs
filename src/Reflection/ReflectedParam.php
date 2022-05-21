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

class ReflectedParam
{
    public string $name;

    public ?TypeNode $type = null;

    public ?TypeNode $nativeType = null;

    public bool $variadic = false;

    public bool $byRef = false;

    public ?string $default = null;

    public string $description = '';

    /**
     * @param string $name Parameter name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        if ($this->type) {
            $this->type = clone $this->type;
        }
        if ($this->nativeType) {
            $this->nativeType = clone $this->nativeType;
        }
    }
}
