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

        $loaded = new LoadedInterface($fqsen, $loadedFile, $interface);
        $this->cache[$fqsen] = $loaded;

        foreach ($interface->getParents() as $parent) {
            //$this->log('Adding parent: ' . (string)$parent, 'info');
            $loadedParent = $this->getInterface((string)$parent);
            $loaded->addInterface((string)$parent, $loadedParent);
        }

        $this->addConstants($loaded, $interface);
        $this->addMethods($loaded, $interface);

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

        $loaded = new LoadedClass($fqsen, $loadedFile, $class);
        $this->cache[$fqsen] = $loaded;

        foreach ($class->getInterfaces() as $interface) {
            $loadedInterface = $this->getInterface((string)$interface);
            $loaded->addInterface((string)$interface, $loadedInterface);
        }

        $extends = $class->getParent();
        if ($extends) {
            $loadedExtends = $this->getClass((string)$extends);
            $loaded->addExtends((string)$extends, $loadedExtends);
        }

        foreach ($class->getUsedTraits() as $usedTrait) {
            $loadedTrait = $this->getTrait((string)$usedTrait);
            $loaded->addTrait((string)$usedTrait, $loadedTrait);
        }

        $this->addConstants($loaded, $class);
        $this->addProperties($loaded, $class);
        $this->addMethods($loaded, $class);

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

        $loaded = new LoadedTrait($fqsen, $loadedFile, $trait);
        $this->cache[$fqsen] = $loaded;

        foreach ($trait->getUsedTraits() as $usedTrait) {
            //$this->log('Adding trait:' . (string)$trait, 'info');
            $loadedTrait = $this->getTrait((string)$usedTrait);
            $loaded->addTrait((string)$usedTrait, $loadedTrait);
        }

        $this->addProperties($loaded, $trait);
        $this->addMethods($loaded, $trait);

        return $loaded;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loadedClassLike Loaded class-like
     * @param \phpDocumentor\Reflection\Element $classLike Element instance
     * @return void
     */
    protected function addConstants(LoadedClassLike $loadedClassLike, $classLike): void
    {
        foreach ($classLike->getConstants() as $fqsen => $constant) {
            $loadedConstant = $loadedClassLike->constants[$constant->getName()] ?? null;
            if ($loadedConstant === null) {
                $loadedConstant = new LoadedConstant(
                    $constant->getName(),
                    $fqsen,
                    $loadedClassLike->fqsen
                );
                $loadedClassLike->constants[$constant->getName()] = $loadedConstant;
            }
            $loadedConstant->declarations[] = $constant;
        }
        ksort($loadedClassLike->constants);
        foreach ($loadedClassLike->constants as $constant) {
            $constant->merge();
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loadedClassLike Loaded class-like
     * @param \phpDocumentor\Reflection\Element $classLike Element instance
     * @return void
     */
    protected function addProperties(LoadedClassLike $loadedClassLike, $classLike): void
    {
        foreach ($classLike->getProperties() as $fqsen => $property) {
            $loadedProperty = $loadedClassLike->properties[$property->getName()] ?? null;
            if ($loadedProperty === null) {
                $loadedProperty = new LoadedProperty(
                    $property->getName(),
                    $fqsen,
                    $loadedClassLike->fqsen
                );
                $loadedClassLike->properties[$property->getName()] = $loadedProperty;
            }
            $loadedProperty->declarations[] = $property;
        }
        ksort($loadedClassLike->properties);
        foreach ($loadedClassLike->properties as $property) {
            $property->merge();
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $loadedClassLike Loaded class-like
     * @param \phpDocumentor\Reflection\Element $classLike Element instance
     * @return void
     */
    protected function addMethods(LoadedClassLike $loadedClassLike, $classLike): void
    {
        foreach ($classLike->getMethods() as $fqsen => $method) {
            $loadedMethod = $loadedClassLike->methods[$method->getName()] ?? null;
            if ($loadedMethod === null) {
                $loadedMethod = new LoadedMethod(
                    $method->getName(),
                    $fqsen,
                    $loadedClassLike->fqsen
                );
                $loadedClassLike->methods[$method->getName()] = $loadedMethod;
            }
            $loadedMethod->declarations[] = $method;
        }

        ksort($loadedClassLike->methods);
        foreach ($loadedClassLike->methods as $method) {
            $method->merge();
        }
    }
}
