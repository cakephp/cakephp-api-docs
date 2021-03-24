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

use Cake\Log\LogTrait;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Property;
use phpDocumentor\Reflection\Php\Visibility;

class Loader
{
    use LogTrait;

    /**
     * @var \Cake\ApiDocs\Reflection\Project
     */
    protected $project;

    /**
     * @var array<string, \Cake\ApiDocs\Reflection\LoadedClassLike|null>
     */
    protected $cache = [];

    /**
     * @param \Cake\ApiDocs\Project $project Project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param string $fqsen Interface fqsen
     * @return \Cake\ApiDocs\Reflection\LoadedInterface|null
     */
    public function getInterface(string $fqsen): ?LoadedInterface
    {
        if (array_key_exists($fqsen, $this->cache)) {
            return $this->cache[$fqsen];
        }

        $loadedFile = $this->project->findFile($fqsen);
        if ($loadedFile === null) {
            $this->cache[$fqsen] = null;

            return null;
        }

        $interface = $loadedFile->file->getInterfaces()[$fqsen] ?? null;
        if ($interface === null) {
            return null;
        }

        $loaded = new LoadedInterface($fqsen, $interface, $loadedFile);
        $this->cache[$fqsen] = $loaded;

        $this->addInterfaces($loaded, $interface->getParents());

        $this->addConstants($loaded, $interface->getConstants());
        $this->addMethods($loaded, $interface->getMethods());

        return $loaded;
    }

    /**
     * @param string $fqsen Class fqsen
     * @return \Cake\ApiDocs\Reflection\LoadedClass|null
     */
    public function getClass(string $fqsen): ?LoadedClass
    {
        if (array_key_exists($fqsen, $this->cache)) {
            return $this->cache[$fqsen];
        }

        $loadedFile = $this->project->findFile($fqsen);
        if ($loadedFile === null) {
            return null;
        }

        $class = $loadedFile->file->getClasses()[$fqsen] ?? null;
        if ($class === null) {
            $this->cache[$fqsen] = null;

            return null;
        }

        $loaded = new LoadedClass($fqsen, $class, $loadedFile);
        $this->cache[$fqsen] = $loaded;

        $this->addInterfaces($loaded, $class->getInterfaces());
        $this->addExtends($loaded, (array)$class->getParent());
        $this->addTraits($loaded, $class->getUsedTraits());

        $this->addConstants($loaded, $class->getConstants());
        $this->addProperties($loaded, $class->getProperties());
        $this->addMethods($loaded, $class->getMethods());

        return $loaded;
    }

    /**
     * @param string $fqsen Trait fqsen
     * @return \Cake\ApiDocs\Reflection\LoadedTrait
     */
    public function getTrait(string $fqsen): LoadedTrait
    {
        if (array_key_exists($fqsen, $this->cache)) {
            return $this->cache[$fqsen];
        }

        $loadedFile = $this->project->findFile($fqsen);
        if ($loadedFile === null) {
            return null;
        }

        $trait = $loadedFile->file->getTraits()[$fqsen] ?? null;
        if ($trait === null) {
            $this->cache[$fqsen] = null;

            return null;
        }

        $loaded = new LoadedTrait($fqsen, $trait, $loadedFile);
        $this->cache[$fqsen] = $loaded;

        $this->addTraits($loaded, $trait->getUsedTraits());

        $this->addProperties($loaded, $trait->getProperties());
        $this->addMethods($loaded, $trait->getMethods());

        return $loaded;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param \phpDocumentor\Reflection\Php\Constant[] $constants Reflection constants
     * @return void
     */
    protected function addConstants(LoadedClassLike $loaded, array $constants): void
    {
        foreach ($constants as $fqsen => $constant) {
            $loadedConstant = $loaded->constants[$constant->getName()] ?? null;
            if ($loadedConstant) {
                $loadedConstant->docBlock = $this->mergeDocBlock($loadedConstant->docBlock, $constant->getDocBlock());
                $loadedConstant->origin = $loaded;
            } else {
                $loadedConstant = new LoadedConstant((string)$constant->getFqsen(), $constant, $loaded);
                $loaded->constants[$constant->getName()] = $loadedConstant;
            }
        }

        ksort($loaded->constants);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param \phpDocumentor\Reflection\Php\Property[] $properties Reflection properties
     * @return void
     */
    protected function addProperties(LoadedClassLike $loaded, array $properties): void
    {
        if ($loaded->element->getDocBlock()) {
            foreach ($loaded->element->getDocBlock()->getTags() as $tag) {
                if (
                    !in_array($tag->getName(), ['property', 'property-read', 'property-write'], true) ||
                    isset($loaded->properties[$tag->getVariableName()])
                ) {
                    continue;
                }

                $fqsen = $loaded->fqsen . '::$' . $tag->getVariableName();
                $property = new Property(
                    new Fqsen($fqsen),
                    new Visibility('public'),
                    new DocBlock((string)($tag->getDescription() ?: '')),
                    null,
                    false,
                    null,
                    $tag->getType()
                );
                $loadedProperty = new LoadedProperty($fqsen, $property, $loaded);
                $loadedProperty->annotation = $tag->getName();
                $loaded->properties[$tag->getVariableName()] = $loadedProperty;
            }
        }

        foreach ($properties as $fqsen => $property) {
            $loadedProperty = $loaded->properties[$property->getName()] ?? null;
            if ($loadedProperty) {
                $loadedProperty->docBlock = $this->mergeDocBlock($loadedProperty->docBlock, $property->getDocBlock());
                $loadedProperty->origin = $loaded;
                $loadedProperty->annotation = null;
            } else {
                $loadedProperty = new LoadedProperty((string)$property->getFqsen(), $property, $loaded);
                $loaded->properties[$property->getName()] = $loadedProperty;
            }
        }

        ksort($loaded->properties);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param \phpDocumentor\Reflection\Php\Method[] $methods Reflection methods
     * @return void
     */
    protected function addMethods(LoadedClassLike $loaded, array $methods): void
    {
        if ($loaded->element->getDocBlock()) {
            foreach ($loaded->element->getDocBlock()->getTagsByName('method') as $tag) {
                if (isset($loaded->methods[$tag->getMethodName()])) {
                    continue;
                }

                $fqsen = $loaded->fqsen . '::' . $tag->getMethodName() . '()';
                $method = new Method(
                    new Fqsen($fqsen),
                    new Visibility('public'),
                    new DocBlock((string)($tag->getDescription() ?: '')),
                    false,
                    $tag->isStatic(),
                    false,
                    null,
                    $tag->getReturnType()
                );
                $loadedMethod = new LoadedMethod($fqsen, $method, $loaded);
                $loadedMethod->annotation = 'method';
                $loaded->methods[$tag->getMethodName()] = $loadedMethod;
            }
        }

        foreach ($methods as $fqsen => $method) {
            $loadedMethod = $loaded->methods[$method->getName()] ?? null;
            if ($loadedMethod) {
                $loadedMethod->docBlock = $this->mergeDocBlock($loadedMethod->docBlock, $method->getDocBlock());
                $loadedMethod->origin = $loaded;
                $loadedMethod->annotation = null;
            } else {
                $loadedMethod = new LoadedMethod((string)$method->getFqsen(), $method, $loaded);
                $loaded->methods[$method->getName()] = $loadedMethod;
            }
        }

        ksort($loaded->methods);
    }

    /**
     * @param \phpDocumentor\Reflection\DocBlock $first First docblock
     * @param \phpDocumentor\Reflection\DocBlock|null $second Second docblock
     * @return \phpDocumentor\Reflection\DocBlock
     */
    protected function mergeDocBlock(DocBlock $first, ?DocBlock $second): DocBlock
    {
        if ($second === null) {
            return $first;
        }

        $inheritTags = array_filter($second->getTags(), function ($tag) {
            if (preg_match('/inheritDoc/i', $tag->getName()) === 1) {
                return true;
            }
        });

        if (empty($inheritTags) && preg_match('/{@inheritDoc}/i', $second->getSummary()) == false) {
            return $second;
        }

        $summary = $first->getSummary();
        $body = $first->getDescription()->getBodyTemplate();
        if (!empty($body) && !empty($second->getDescription()->getBodyTemplate())) {
            $body .= "\n\n---\n\n";
        }
        $body .= $second->getDescription()->getBodyTemplate();

        $description = new Description(
            $body,
            array_merge($first->getDescription()->getTags(), $second->getDescription()->getTags())
        );

        return new DocBlock(
            $summary,
            $description,
            array_merge($first->getTags(), $second->getTags()),
            $first->getContext(),
            $second->getLocation()
        );
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param \phpDocumentor\Reflection\Fqsen[] $fqsens Interface fqsens
     * @return void
     */
    protected function addInterfaces(LoadedClassLike $loaded, array $fqsens): void
    {
        foreach ($fqsens as $fqsen) {
            $loadedInterface = $this->getInterface((string)$fqsen);
            $loaded->interfaces[(string)$fqsen] = $loadedInterface;

            if (!$loadedInterface) {
                continue;
            }

            $this->mergeConstants($loaded, $loadedInterface->constants);
            $this->mergeMethods($loaded, $loadedInterface->methods);
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param \phpDocumentor\Reflection\Fqsen[] $fqsens Class fqsens
     * @return void
     */
    protected function addExtends(LoadedClassLike $loaded, array $fqsens): void
    {
        foreach ($fqsens as $fqsen) {
            $loadedClass = $this->getClass((string)$fqsen);
            $loaded->extends[(string)$fqsen] = $loadedClass;

            if (!$loadedClass) {
                continue;
            }

            $this->mergeConstants($loaded, $loadedClass->constants);
            $this->mergeProperties($loaded, $loadedClass->properties);
            $this->mergeMethods($loaded, $loadedClass->methods);
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param \phpDocumentor\Reflection\Fqsen[] $fqsens Trait fqsens
     * @return void
     */
    protected function addTraits(LoadedClassLike $loaded, array $fqsens): void
    {
        foreach ($fqsens as $fqsen) {
            $loadedTrait = $this->getTrait((string)$fqsen);
            $loaded->traits[(string)$fqsen] = $loadedTrait;

            if (!$loadedTrait) {
                continue;
            }

            $this->mergeProperties($loaded, $loadedTrait->properties);
            $this->mergeMethods($loaded, $loadedTrait->methods);
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param array $loadedConstants Loaded constants
     * @return void
     */
    protected function mergeConstants(LoadedClassLike $loaded, array $loadedConstants): void
    {
        foreach ($loadedConstants as $fqsen => $loadedConstant) {
            $existing = $loaded->constants[$loadedConstant->name] ?? null;
            if ($existing) {
                $existing->docBlock = $this->mergeDocBlock($existing->docBlock, $loadedConstant->docBlock);
            } else {
                $loaded->constants[$loadedConstant->name] = clone $loadedConstant;
                $loaded->constants[$loadedConstant->name]->fqsen = $loaded->fqsen . '::' . $loadedConstant->name;
            }
        }
        ksort($loaded->constants);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param array $loadedProperties Loaded properties
     * @return void
     */
    protected function mergeProperties(LoadedClassLike $loaded, array $loadedProperties): void
    {
        foreach ($loadedProperties as $fqsen => $loadedProperty) {
            $existing = $loaded->properties[$loadedProperty->name] ?? null;
            if ($existing) {
                $existing->docBlock = $this->mergeDocBlock($existing->docBlock, $loadedProperty->docBlock);
                $existing->annotation = $loadedProperty->annotation;
            } else {
                $loaded->properties[$loadedProperty->name] = clone $loadedProperty;
                $loaded->properties[$loadedProperty->name]->fqsen = $loaded->fqsen . '::$' . $loadedProperty->name;
            }
        }
        ksort($loaded->properties);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loaded Loaded class-like
     * @param array $loadedMethods Loaded methods
     * @return void
     */
    protected function mergeMethods(LoadedClassLike $loaded, array $loadedMethods): void
    {
        foreach ($loadedMethods as $fqsen => $loadedMethod) {
            $existing = $loaded->methods[$loadedMethod->name] ?? null;
            if ($existing) {
                $existing->docBlock = $this->mergeDocBlock($existing->docBlock, $loadedMethod->docBlock);
                $existing->annotation = $loadedMethod->annotation;
            } else {
                $loaded->methods[$loadedMethod->name] = clone $loadedMethod;
                $loaded->methods[$loadedMethod->name]->fqsen = $loaded->fqsen . '::' . $loadedMethod->name . '()';
            }
        }
        ksort($loaded->methods);
    }
}
