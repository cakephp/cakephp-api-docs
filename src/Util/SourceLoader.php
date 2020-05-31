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

namespace Cake\ApiDocs\Util;

use Cake\ApiDocs\Reflection\ElementInfo;
use Cake\ApiDocs\Reflection\NamespaceInfo;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\File;
use phpDocumentor\Reflection\Php\Namespace_;
use phpDocumentor\Reflection\Php\ProjectFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * SourceLoader
 */
class SourceLoader
{
    /**
     * @var string
     */
    protected $sourceDir;

    /**
     * @var \phpDocumentor\Reflection\Php\ProjectFactory
     */
    protected $factory;

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $classLoader;

    /**
     * @var \phpDocumentor\Reflection\Php\File[]
     */
    protected $projectFiles = [];

    /**
     * @var \phpDocumentor\Reflection\Php\Namespace_[]
     */
    protected $projectNamespaces = [];

    /**
     * @var \phpDocumentor\Reflection\Php\File[]
     */
    protected $vendorFiles = [];

    /**
     * @var (null|\Cake\ApiDocs\Reflection\NamespaceInfo)[]
     */
    protected $cachedNamespaceInfo = [];

    /**
     * @var (null|\Cake\ApiDocs\Reflection\ElementInfo)[]
     */
    protected $cachedElementInfo = [];

    /**
     * @param string $sourceDir Source directory
     */
    public function __construct(string $sourceDir)
    {
        $this->sourceDir = $sourceDir;
        $this->factory = ProjectFactory::createInstance();
        $this->findClassLoader();
        $this->loadReflection();
    }

    /**
     * @return \phpDocumentor\Reflection\Php\File[]
     */
    public function getFiles(): array
    {
        return $this->projectFiles;
    }

    /**
     * @return \Cake\ApiDocs\Reflection\NamespaceInfo[]
     */
    public function getNamespaces(): array
    {
        return $this->projectNamespaces;
    }

    /**
     * @param string $fqsen fqsen
     * @return \Cake\ApiDocs\Reflection\NamespaceInfo|null
     */
    public function getNamespaceInfo(string $fqsen): ?NamespaceInfo
    {
        if (array_key_exists($fqsen, $this->cachedNamespaceInfo)) {
            return $this->cachedNamespaceInfo[$fqsen];
        }

        $info = $this->buildNamespaceInfo($fqsen);
        $this->cachedNamespaceInfo[$fqsen] = $info;

        return $info;
    }

    /**
     * @param string $fqsen fqsen
     * @return \Cake\ApiDocs\Reflection\ElementInfo|null
     */
    public function getElementInfo(string $fqsen): ?ElementInfo
    {
        if (array_key_exists($fqsen, $this->cachedElementInfo)) {
            return $this->cachedElementInfo[$fqsen];
        }

        $info = $this->buildElementInfo($fqsen);
        $this->cachedElementInfo[$fqsen] = $info;

        return $info;
    }

    /**
     * @param string $fqsen fqsen
     * @return \Cake\ApiDocs\Reflection\NamespaceInfo
     */
    protected function buildNamespaceInfo(string $fqsen): NamespaceInfo
    {
        $namespace = $this->projectNamespaces[$fqsen];
        $parent = substr($fqsen, 0, strrpos($fqsen, '\\'));
        $parent = $this->projectNamespaces[$parent] ?? null;

        $children = [];
        $quotedFqsen = preg_quote($fqsen);
        $children = array_filter($this->projectNamespaces, function ($fqsen) use ($quotedFqsen) {
            return preg_match('/^' . $quotedFqsen . '\\\\[^\\\\]+$/', $fqsen) === 1;
        }, ARRAY_FILTER_USE_KEY);
        ksort($children);

        return new NamespaceInfo($fqsen, $namespace, $parent, $children);
    }

    /**
     * @param string $fqsen fqsen
     * @return \Cake\ApiDocs\Reflection\ElementInfo|null
     */
    protected function buildElementInfo($fqsen): ?ElementInfo
    {
        $parentFqsen = null;
        $parts = explode('::', $fqsen);
        if (count($parts) > 1) {
            $parentFqsen = current($parts);
        }

        /** @var \phpDocumentor\Reflection\Php\File $file */
        [$file, $inProject] = $this->getFile($parentFqsen ?? $fqsen);
        if (!$file) {
            foreach ($this->projectFiles as $file) {
                foreach (['constants', 'functions'] as $type) {
                    $element = $file->{'get' . $type}()[$fqsen] ?? null;
                    if ($element) {
                        return new ElementInfo($fqsen, $element, null, $file, true);
                    }
                }
            }

            return null;
        }

        if ($parentFqsen) {
            $parentElement = $this->getClassLike($file, $parentFqsen);
            foreach (['methods', 'constants', 'properties'] as $type) {
                if (method_exists($parentElement, 'get' . $type)) {
                    $element = $parentElement->{'get' . $type}()[$fqsen] ?? null;
                    if ($element) {
                        return new ElementInfo($fqsen, $element, $parentElement, $file, $inProject);
                    }
                }
            }

            return null;
        }

        foreach (['classes', 'interfaces', 'traits', 'constants', 'functions'] as $type) {
            $element = $file->{'get' . $type}()[$fqsen] ?? null;
            if ($element) {
                return new ElementInfo($fqsen, $element, null, $file, $inProject);
            }
        }

        return null;
    }

    /**
     * @param \phpDocumentor\Reflection\Php\File $file The reflection file
     * @param string $fqsen The class fqsen
     * @return \phpDocumentor\Reflection\Element|null
     */
    protected function getClassLike(File $file, string $fqsen): ?Element
    {
        foreach (['classes', 'interfaces', 'traits'] as $type) {
            $class = $file->{'get' . $type}()[$fqsen] ?? null;
            if ($class) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @param string $fqsen A loadable fqsen
     * @return array
     */
    protected function getFile(string $fqsen): array
    {
        if ($fqsen[0] === '\\') {
            $fqsen = substr($fqsen, 1);
        }

        $path = $this->classLoader->findFile($fqsen);
        if ($path === false) {
            return [null, false];
        }

        $path = realpath($path);
        if (isset($this->projectFiles[$path])) {
            return [$this->projectFiles[$path], true];
        }

        if (empty($this->vendorFiles[$path])) {
            $tmpProject = $this->factory->create('tmp', [new LocalFile($path)]);
            $file = current($tmpProject->getFiles());
            $this->vendorFiles[$path] = $file;
        }

        return [$this->vendorFiles[$path], false];
    }

    /**
     * @return void
     */
    protected function findClassLoader(): void
    {
        // try to find vendor/ relative to sourceDir
        $vendorDir = $this->sourceDir;
        foreach (range(0, 2) as $parentNum) {
            $autoloadPath = $vendorDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (file_exists($autoloadPath)) {
                api_log('notice', "Found vendor autoload at {$autoloadPath}.");
                $this->classLoader = require $autoloadPath;
                $this->classLoader->unregister();
                break;
            }
            $vendorDir = dirname($vendorDir);
        }

        if ($this->classLoader === null) {
            api_log('notice', 'No vendor autoload found. Dependencies will not be parsed.');
        }
    }

    /**
     * @return void
     */
    protected function loadReflection(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir)
        );

        $files = [];
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            if (preg_match('/^.+\.php$/i', $entry->getFilename())) {
                $files[] = new LocalFile((string)$entry);
            }
        }

        $project = $this->factory->create('project', $files);
        foreach ($project->getFiles() as $path => $file) {
            $this->projectFiles[realpath($path)] = $file;
        }

        foreach ($project->getNamespaces() as $fqsen => $namespace) {
            $parentFqsen = substr($fqsen, 0, strrpos($fqsen, '\\'));
            if ($parentFqsen && !isset($project->getNamespaces()[$parentFqsen])) {
                $this->projectNamespaces[$parentFqsen] = new Namespace_(new Fqsen($parentFqsen));
            }
            $this->projectNamespaces[$fqsen] = $namespace;
        }
        ksort($this->projectNamespaces);
    }
}
