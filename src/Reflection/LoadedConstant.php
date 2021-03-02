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

use phpDocumentor\Reflection\Php\Constant;

class LoadedConstant
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $origin;

    /**
     * @var string
     */
    public string $owner;

    /**
     * @var \phpDocumentor\Reflection\Php\Constant
     */
    public Constant $constant;

    /**
     * @param string $name Constant name
     * @param string $origin Fqsen where constant was declared
     * @param string $owner Fqsen that is using the method
     * @param \phpDocumentor\Reflection\Php\Constant $constant Constant instance
     */
    public function __construct(string $name, string $origin, string $owner, Constant $constant)
    {
        $this->name = $name;
        $this->origin = $origin;
        $this->owner = $owner;
        $this->constant = $constant;
    }
}
