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

use PhpParser\Node\Stmt\ClassLike;

abstract class LoadedClassLike extends LoadedNode
{
    public array $uses = [];

    public array $constants = [];

    public array $properties = [];

    public array $methods = [];

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node Classlike node
     * @param \Cake\ApiDocs\Reflection\Source $source Node source
     * @param \Cake\ApiDocs\Reflection\Context $context Node context
     */
    public function __construct(ClassLike $node, Source $source, Context $context)
    {
        parent::__construct($node->name->name, $source, $context, $node->getDocComment()?->getText());

        $parentContext = clone $context;
        $parentContext->parent = $context->namespaced($this->name);

        foreach ($node->getConstants() as $classConst) {
            $visibility = 'public';
            if ($classConst->isProtected()) {
                $visibility = 'protected';
            } elseif ($classConst->isPrivate()) {
                $visibility = 'private';
            }

            foreach ($classConst->consts as $const) {
                $this->constants[$const->name->name] = $loaded = new LoadedConstant(
                    $const,
                    new Source($source->path, $const->getStartLine(), $const->getEndLine()),
                    $parentContext
                );
                $loaded->visibility = $visibility;
            }
        }

        foreach ($node->getProperties() as $classProperty) {
            $visibility = 'public';
            if ($classProperty->isProtected()) {
                $visibility = 'protected';
            } elseif ($classProperty->isPrivate()) {
                $visibility = 'private';
            }

            foreach ($classProperty->props as $property) {
                $this->properties[$property->name->name] = $loaded = new LoadedProperty(
                    $classProperty,
                    $property,
                    new Source($source->path, $property->getStartLine(), $property->getEndLine()),
                    $parentContext
                );
                $loaded->visibility = $visibility;
                $loaded->static = $classProperty->isStatic();
            }
        }

        foreach ($node->getmethods() as $method) {
            $this->methods[$method->name->name] = $loaded = new LoadedFunction(
                $method,
                new Source($source->path, $method->getStartLine(), $method->getEndLine()),
                $parentContext
            );

            if ($method->isProtected()) {
                $loaded->visibility = 'protected';
            } elseif ($method->isPrivate()) {
                $loaded->visibility = 'private';
            } else {
                $loaded->visibility = 'public';
            }
        }
    }
}
