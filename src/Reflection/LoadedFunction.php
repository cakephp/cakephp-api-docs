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

use phpDocumentor\Reflection\Php\Function_;

class LoadedFunction
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $origin;

    /**
     * @var string
     */
    public $owner;

    /**
     * @var \phpDocumentor\Reflection\Php\Constant
     */

    /**
     * @param string $name Function name
     * @param string $origin Fqsen where function was declared
     * @param string $owner Fqsen that is using the function
     * @param \phpDocumentor\Reflection\Php\Function_ $function Function instance
     */
    public function __construct(string $name, string $origin, string $owner, Function_ $function)
    {
        $this->name = $name;
        $this->origin = $origin;
        $this->owner = $owner;
        $this->function = $function;
    }
}
