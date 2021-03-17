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
use phpDocumentor\Reflection\Php\Constant;

class LoadedConstant
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
     * @var \phpDocumentor\Reflection\Php\Constant
     */
    public Constant $constant;

    /**
     * @var \phpDocumentor\Reflection\DocBlock
     */
    public DocBlock $docBlock;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedNamespace|\Cake\ApiDocs\Reflection\LoadedClassLike|null
     */
    public $origin;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedInterface|null
     */
    public ?LoadedInterface $implements;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Constant $constant Reflection constant
     * @param \Cake\ApiDocs\Reflection\LoadedNamespace|\Cake\ApiDocs\Reflection\LoadedClassLike|null $origin Loaded origin
     */
    public function __construct(string $fqsen, Constant $constant, $origin)
    {
        $this->fqsen = $fqsen;
        $this->namespace = substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
        $this->name = $constant->getName();
        $this->constant = $constant;
        $this->docBlock = $constant->getDocBlock() ?? new DocBlock();
        $this->origin = $origin;
        if ($origin instanceof LoadedInterface) {
            $this->implements = $origin;
        }
    }
}
