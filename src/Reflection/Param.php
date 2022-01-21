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

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class Param
{
    /**
     * @param string $name Parameter name
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode|null $type Parameter type
     * @param bool $variadic Variadic parameter
     * @param bool $reference Reference parameter
     * @param string|null $default Default value
     * @param string|null $description Parameter description
     */
    public function __construct(
        public string $name,
        public ?TypeNode $type = null,
        public bool $variadic = false,
        public bool $reference = false,
        public ?string $default = null,
        public string $description = ''
    ) {
    }
}
