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

use phpDocumentor\Reflection\Php\Namespace_;

class LoadedNamespace
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
     * @var \phpDocumentor\Reflection\Php\Namespace_
     */
    public Namespace_ $element;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedNamespace[]
     */
    public array $children = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedInterface[]
     */
    public array $interfaces = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedClass[]
     */
    public array $classes = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedTrait[]
     */
    public array $traits = [];

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Namespace_ $element Namespace instance
     */
    public function __construct(string $fqsen, Namespace_ $element)
    {
        $this->fqsen = $fqsen;
        $this->namespace = substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
        $this->element = $element;
    }
}
