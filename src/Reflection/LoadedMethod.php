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
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\Php\Method;
use RuntimeException;

class LoadedMethod
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
     * @var \phpDocumentor\Reflection\Php\Method[]
     */
    public array $declarations = [];

    /**
     * @var \phpDocumentor\Reflection\Php\Method
     */
    public Method $method;

    /**
     * @param string $name Method name
     * @param string $origin Fqsen where method was declared
     * @param string $owner Fqsen that is using the method
     */
    public function __construct(string $name, string $origin, string $owner)
    {
        $this->name = $name;
        $this->origin = $origin;
        $this->owner = $owner;
    }

    /**
     * Merge all declarations into single method.
     *
     * @return void
     */
    public function merge(): void
    {
        if (empty($this->declarations)) {
            throw new RuntimeException("No method declarations found for `$this->name`.");
        }

        $merged = ['summary' => '', 'body' => '', 'bodyTags' => [], 'tags' => []];
        foreach (array_reverse($this->declarations) as $method) {
            $docBlock = $method->getDocBlock();
            if ($docBlock === null) {
                continue;
            }

            if (!empty($merged['body'])) {
                $merged['body'] .= "\n---\n";
            }
            $merged['body'] .= $docBlock->getDescription()->getBodyTemplate();
            $merged['bodyTags'] = array_merge($merged['bodyTags'], $docBlock->getDescription()->getTags());
            $merged['tags'] = array_merge($merged['tags'], $docBlock->getTags());

            $inheritTags = array_filter($docBlock->getTags(), function ($tag) {
                if (preg_match('/inheritDoc/i', $tag->getName()) === 1) {
                    return true;
                }
            });
            if (empty($inheritTags) && preg_match('/@inheritDoc/i', $docBlock->getSummary()) !== 1) {
                break;
            }
        }
        $merged['summary'] = ($method->getDocBlock() ?? new DocBlock())->getSummary();

        $definition = end($this->declarations);
        $definitionBlock = $definition->getDocBlock() ?? new DocBlock();
        $mergedBlock = new DocBlock(
            $merged['summary'],
            new Description($merged['body'], $merged['bodyTags']),
            $merged['tags'],
            $definitionBlock->getContext(),
            $definitionBlock->getLocation()
        );

        $this->method = new Method(
            $definition->getFqsen(),
            $definition->getVisibility(),
            $mergedBlock,
            $definition->isAbstract(),
            $definition->isStatic(),
            $definition->isFinal(),
            $definition->getLocation(),
            $definition->getReturnType()
        );
        foreach ($definition->getArguments() as $argument) {
            $this->method->addArgument($argument);
        }
    }
}
