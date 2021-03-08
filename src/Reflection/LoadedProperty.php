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

use phpDocumentor\Reflection\Php\Property;

class LoadedProperty
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
     * @var \phpDocumentor\Reflection\Php\Property
     */
    public Property $property;

    /**
     * @param string $name Property name
     * @param string $origin Fqsen where property was declared
     * @param string $owner Fqsen that is using the property
     * @param \phpDocumentor\Reflection\Php\Property $property Property instance
     */
    public function __construct(string $name, string $origin, string $owner, Property $property)
    {
        $this->name = $name;
        $this->origin = $origin;
        $this->owner = $owner;
        $this->property = $property;
    }
}
