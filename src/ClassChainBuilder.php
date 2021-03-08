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

namespace Cake\ApiDocs;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Argument;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Property;
use phpDocumentor\Reflection\Php\Visibility;

class ClassChainBuilder
{
    /**
     * @var \Cake\ApiDocs\Project
     */
    protected $project;

    /**
     * @var array
     */
    protected $chainCache = [];

    /**
     * @var array
     */
    protected $loadOrderCache = [];

    /**
     * Constructor.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param \phpDocumentor\Reflection\Php\Class_|\phpDocumentor\Reflection\Php\Interface_|\phpDocumentor\Reflection\Php\Trait_ $classLike Reflection element
     * @return array
     */
    public function buildChain(Element $classLike)
    {
        $fqsen = (string)$classLike->getFqsen();
        $cached = $this->chainCache[$fqsen] ?? null;
        if ($cached) {
            return $cached;
        }

        $loadOrder = $this->buildLoadOrder($classLike);

        $chain = [
            'loadOrder' => $loadOrder,
            'constants' => $this->buildConstantsChain($loadOrder),
            'properties' => $this->buildPropertiesChain($loadOrder),
            'methods' => $this->buildMethodsChain($loadOrder),
        ];

        $this->chainCache[$fqsen] = $chain;

        return $chain;
    }

    /**
     * @param array $chain Element chain
     * @return \phpDocumentor\Reflection\DocBlock
     */
    public function buildDocBlock(array $chain): DocBlock
    {
        /** @var \phpDocumentor\Reflection\DocBlock[] $blocks */
        $block = ['tags' => [], 'body' => ''];
        foreach ($chain as $element) {
            $docBlock = $element->getDocBlock();
            if ($docBlock === null) {
                break;
            }

            $blocks[] = ['tags'];

            $inheritTags = array_filter($docBlock->getTags(), function ($tag) {
                if (preg_match('/inheritDoc/i', $tag->getName()) === 1) {
                    return true;
                }
            });
            if (empty($inheritTags) || preg_match('/{@inheritDoc}/i', $docBlock->getSummary()) !== 1) {
                break;
            }
        }

        if (empty($blocks)) {
            return new DocBlock();
        }

        $tags = [];
        $body = '';
        $bodyTags = [];
        $blocks = array_reverse($blocks);
        foreach ($blocks as $block) {
            $tags = array_merge($tags, $block->getTags());
            $description = $block->getDescription();
            if ($description && !empty(trim($description->getBodyTemplate()))) {
                $body = $body . "\n### Appended\n" . $description->getBodyTemplate();
                $bodyTags = array_merge($bodyTags, $description->getTags());
            }
        }

        return new DocBlock($blocks[0]->getSummary(), new Description($body, $bodyTags), $tags);
    }

    /**
     * @param \phpDocumentor\Reflection\Php\Class_|\phpDocumentor\Reflection\Php\Interface_|\phpDocumentor\Reflection\Php\Trait_ $classLike Reflection element
     * @return array
     */
    protected function buildLoadOrder(Element $classLike): array
    {
        $fqsen = (string)$classLike->getFqsen();
        $cached = $this->loadOrderCache[$fqsen] ?? null;
        if ($cached) {
            return $cached;
        }

        $loadOrder = [$fqsen => $classLike];
        foreach (['getUsedTraits', 'getInterfaces', 'getParents', 'getParent'] as $getter) {
            if (!method_exists($classLike, $getter)) {
                continue;
            }

            $fqsens = (array)$classLike->{$getter}();
            foreach ($fqsens as $fqsen) {
                $nextClassLike = $this->project->getClassLike($fqsen);
                if ($nextClassLike) {
                    $loadOrder += $this->buildLoadOrder($nextClassLike);
                }
            }
        }

        $this->loadOrderCache[$fqsen] = $loadOrder;

        return $loadOrder;
    }

    /**
     * @param array $loadOrder Load order
     * @return array
     */
    public function buildConstantsChain(array $loadOrder): array
    {
        $chain = [];
        foreach ($loadOrder as $classLike) {
            if (method_exists($classLike, 'getConstants')) {
                foreach ($classLike->getConstants() as $fqsen => $constant) {
                    $chain[(string)$constant->getFqsen()][] = $constant;
                }
            }
        }

        return $chain;
    }

    /**
     * @param array $loadOrder Load order
     * @return array
     */
    public function buildPropertiesChain(array $loadOrder): array
    {
        $chain = [];
        foreach ($loadOrder as $classLike) {
            if (method_exists($classLike, 'getProperties')) {
                foreach ($classLike->getProperties() as $fqsen => $property) {
                    $chain[$property->getName()][] = $property;
                }
            }

            $docBlock = $classLike->getDocBlock();
            if ($docBlock) {
                $tags = $docBlock->getTagsByName('property');
                $tags += $docBlock->getTagsByName('property-read');
                $tags += $docBlock->getTags('property-write');
                foreach ($tags as $tag) {
                    $description = $tag->getDescription() ?? new Description('');
                    $chain[$tag->getVariableName()][] = new Property(
                        new Fqsen((string)$classLike->getFqsen() . '::' . $tag->getVariableName()),
                        new Visibility(Visibility::PUBLIC_),
                        new DocBlock($description->getBodyTemplate()),
                        null,
                        false,
                        null,
                        $tag->getType()
                    );
                }
            }
        }

        return $chain;
    }

    /**
     * @param array $loadOrder Load order
     * @return array
     */
    public function buildMethodsChain(array $loadOrder): array
    {
        $chain = [];
        foreach ($loadOrder as $classLike) {
            if (method_exists($classLike, 'getMethods')) {
                foreach ($classLike->getMethods() as $fqsen => $method) {
                    $chain[$method->getName()][] = $method;
                }
            }

            $docBlock = $classLike->getDocBlock();
            if ($docBlock) {
                $tags = $docBlock->getTagsByName('method');
                foreach ($tags as $tag) {
                    $description = $tag->getDescription() ?? new Description('');
                    $method = new Method(
                        new Fqsen((string)$classLike->getFqsen() . '::' . $tag->getMethodName() . '()'),
                        new Visibility(Visibility::PUBLIC_),
                        new DocBlock($description->getBodyTemplate()),
                        false,
                        $tag->isStatic(),
                        false,
                        null,
                        $tag->getReturnType()
                    );
                    foreach ($tag->getArguments() as $argument) {
                        $method->addArgument(new Argument($argument['name'], $argument['type']));
                    }

                    $chain[$tag->getMethodName()][] = $method;
                }
            }
        }

        return $chain;
    }
}
