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

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Php\Property;

class LoadedProperty
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
     * @var \phpDocumentor\Reflection\Php\Property
     */
    public Property $property;

    /**
     * @var \phpDocumentor\Reflection\DocBlock
     */
    public DocBlock $docBlock;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedClassLike
     */
    public LoadedClassLike $origin;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Property $property Reflection property
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $origin Origin loaded class-like
     */
    public function __construct(string $fqsen, Property $property, LoadedClassLike $origin)
    {
        $this->fqsen = $fqsen;
        $this->namespace = substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
        $this->name = $property->getName();
        $this->property = $property;
        $this->docBlock = $property->getDocBlock() ?? new DocBlock();
        $this->origin = $origin;
    }
}
