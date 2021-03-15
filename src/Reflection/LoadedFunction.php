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
    public string $fqsen;

    /**
     * @var string
     */
    public string $namespace;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var \phpDocumentor\Reflection\Php\Function
     */
    public Function_ $function;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Function_ $function Reflection function
     */
    public function __construct(string $fqsen, Function_ $function)
    {
        $this->fqsen = $fqsen;
        $this->namespace = substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
        $this->name = $function->getName();
        $this->function = $function;
    }
}
