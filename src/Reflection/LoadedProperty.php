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
use phpDocumentor\Reflection\Fqsen;
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
     * @var \phpDocumentor\Reflection\Php\Property[]
     */
    public array $declarations = [];

    /**
     * @var \phpDocumentor\Reflection\Php\Property
     */
    public Property $property;

    /**
     * @param string $name Property name
     * @param string $origin Fqsen where property was declared
     * @param string $owner Fqsen that is using the property
     */
    public function __construct(string $name, string $origin, string $owner)
    {
        $this->name = $name;
        $this->origin = $origin;
        $this->owner = $owner;
    }

    /**
     * Merge all declarations into single property.
     *
     * @return void
     */
    public function merge(): void
    {
        if (empty($this->declarations)) {
            throw new RuntimeException("No property declarations found for `$this->name`.");
        }

        $merged = ['summary' => '', 'body' => '', 'bodyTags' => [], 'tags' => []];
        foreach (array_reverse($this->declarations) as $property) {
            $docBlock = $property->getDocBlock();
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
        $merged['summary'] = ($property->getDocBlock() ?? new DocBlock())->getSummary();

        $definition = end($this->declarations);
        $definitionBlock = $definition->getDocBlock() ?? new DocBlock();
        $mergedBlock = new DocBlock(
            $merged['summary'],
            new Description($merged['body'], $merged['bodyTags']),
            $merged['tags'],
            $definitionBlock->getContext(),
            $definitionBlock->getLocation()
        );

        $fqsen = $this->owner . '::$' . $definition->getFqsen()->getName();
        $this->property = new Property(
            new Fqsen($fqsen),
            $definition->getVisibility(),
            $mergedBlock,
            $definition->getDefault(),
            $definition->isStatic(),
            $definition->getLocation(),
            $definition->getType()
        );
    }
}
