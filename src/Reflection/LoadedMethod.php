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
use phpDocumentor\Reflection\Php\Method;

class LoadedMethod
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
     * @var \phpDocumentor\Reflection\Php\Method
     */
    public Method $method;

    /**
     * @var \phpDocumentor\Reflection\DocBlock
     */
    public DocBlock $docBlock;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedClassLike
     */
    public LoadedClassLike $origin;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedInterface|null
     */
    public ?LoadedInterface $implements;

    /**
     * @var string|null
     */
    public ?string $annotation = null;

    /**
     * @var string
     */
    public string $filePath;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Method $method Reflection method
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $origin Origin loaded class-like
     */
    public function __construct(string $fqsen, Method $method, LoadedClassLike $origin)
    {
        $this->fqsen = $fqsen;
        $this->namespace = substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
        $this->name = $method->getName();
        $this->method = $method;
        $this->docBlock = $method->getDocBlock() ?? new DocBlock();
        $this->origin = $origin;
        if ($origin instanceof LoadedInterface) {
            $this->implements = $origin;
        }
        $this->filePath = $origin->loadedFile->file->getPath();
    }
}
