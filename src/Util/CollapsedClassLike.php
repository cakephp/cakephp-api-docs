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

namespace Cake\ApiDocs\Util;

use Cake\ApiDocs\Reflection\ElementInfo;

/**
 * CollapsedClassLike
 */
class CollapsedClassLike
{
    /**
     * @var \Cake\ApiDocs\Reflection\ElementInfo
     */
    protected $source;

    /**
     * @var array
     */
    protected $constants = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @param \Cake\ApiDocs\Reflection\ElementInfo $source loaded fsqen
     * @param array $constants constants
     * @param array $properties properties
     * @param array $methods methods
     */
    public function __construct(ElementInfo $source, array $constants, array $properties, array $methods)
    {
        $this->source = $source;
        $this->constants = $constants;
        $this->properties = $properties;
        $this->methods = $methods;
    }

    /**
     * @return \Cake\ApiDocs\Reflection\ElementInfo
     */
    public function getSource(): ElementInfo
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}
