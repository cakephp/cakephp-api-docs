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

use Cake\Core\Configure;
use Cake\Log\LogTrait;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Namespace_;
use phpDocumentor\Reflection\Php\Project as ReflectionProject;
use phpDocumentor\Reflection\Php\ProjectFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Project
{
    use LogTrait;

    /**
     * @var \Cake\ApiDocs\Reflection\Loader
     */
    protected $loader;

    /**
     * @var \phpDocumentor\Reflection\Php\ProjectFactory
     */
    protected $factory;

    /**
     * @var \Composer\Autoload\ClassLoader|null
     */
    protected $classLoader;

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedFile[]
     */
    protected $projectFiles = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedFile[]
     */
    protected $vendorFiles = [];

    /**
     * @var \Cake\ApiDocs\Reflection\LoadedNamespace[]
     */
    protected $projectNamespaces = [];

    /**
     * @param string $projectPath Project path
     */
    public function __construct(string $projectPath)
    {
        $this->loader = new Loader($this);
        $this->factory = ProjectFactory::createInstance();
        $this->loadClassLoader($projectPath);
        $this->loadProjectFiles($projectPath);
    }

    /**
     * Returns reflection loader.
     *
     * @return \Cake\ApiDocs\Reflection\Loader
     */
    public function getLoader(): Loader
    {
        return $this->loader;
    }

    /**
     * Return files loaded from project path.
     *
     * @return \Cake\ApiDocs\Reflection\LoadedFile[]
     */
    public function getProjectFiles(): array
    {
        return $this->projectFiles;
    }

    /**
     * Returns the namespaces loaded from project path.
     *
     * @return \Cake\ApiDocs\Reflection\LoadedNamespace[]
     */
    public function getProjectNamespaces(): array
    {
        return $this->projectNamespaces;
    }

    /**
     * Finds file that contains class-like fqsen
     *
     * @param string $fqsen Class-like fqsen
     * @return \Cake\ApiDocs\Reflection\LoadedFile|null
     */
    public function findFile(string $fqsen): ?LoadedFile
    {
        $path = $this->classLoader->findFile(trim($fqsen, '\\'));
        if ($path === false) {
            return null;
        }

        $path = realpath($path);
        if (isset($this->projectFiles[$path])) {
            return $this->projectFiles[$path];
        }

        if (!isset($this->vendorFiles[$path])) {
            $vendorProject = $this->factory->create('vendor', [new LocalFile($path)]);
            $this->vendorFiles[$path] = new LoadedFile(current($vendorProject->getFiles()), true);
        }

        return $this->vendorFiles[$path];
    }

    /**
     * @param string $projectPath Project path
     * @return void
     */
    protected function loadClassLoader(string $projectPath): void
    {
        // try to find vendor/ relative to sourceDir
        $autoloadPath = $projectPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new RuntimeException("Unable to find class loader at `$autoloadPath`.");
        }

        $this->log("Found class loader at `$autoloadPath`", 'info');
        $this->classLoader = require $autoloadPath;
        $this->classLoader->unregister();
    }

    /**
     * @param string $projectPath Project path
     * @return void
     */
    protected function loadProjectFiles(string $projectPath): void
    {
        $localFiles = [];
        foreach (Configure::read('sourcePaths') as $sourcePath) {
            $filesPath = $projectPath . DIRECTORY_SEPARATOR . $sourcePath;
            $this->log("Loading project files from `$filesPath`", 'info');

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($filesPath)
            );

            foreach ($iterator as $entry) {
                if ($entry->isDir()) {
                    continue;
                }

                if (preg_match('/^.+\.php$/i', $entry->getFilename())) {
                    $localFiles[] = new LocalFile((string)$entry);
                }
            }
        }

        $project = $this->factory->create('project', $localFiles);
        foreach ($project->getFiles() as $path => $file) {
            $this->projectFiles[realpath($path)] = new LoadedFile($file, false);
        }

        $this->loadNamespaces($project);
    }

    /**
     * @param \phpDocumentor\Reflection\Php\Project $project Reflection project
     * @return void
     */
    protected function loadNamespaces(ReflectionProject $project): void
    {
        $namespaces = [];
        foreach ($project->getNamespaces() as $fqsen => $namespace) {
            if (isExcluded($fqsen, true)) {
                continue;
            }

            // Parent namespaces that have no files are not built by ReflectionProject
            $this->addMissingParents($fqsen, $namespaces);

            $namespaces[$fqsen] = $this->loadNamespace($namespace);
        }
        ksort($namespaces);

        // Create nested array
        foreach ($namespaces as $fqsen => $loaded) {
            if ($fqsen === Configure::read('namespace')) {
                $this->projectNamespaces[$fqsen] = $loaded;
                continue;
            }

            $parent = substr($fqsen, 0, strrpos($fqsen, '\\'));
            $namespaces[$parent]->children[$fqsen] = $loaded;
        }
    }

    /**
     * Loads a single namespace.
     *
     * @param \phpDocumentor\Reflection\Php\Namespace_ $namespace Reflection namespace
     * @return \Cake\ApiDocs\Reflection\LoadedNamespace
     */
    protected function loadNamespace(Namespace_ $namespace): LoadedNamespace
    {
        $fqsen = (string)$namespace->getFqsen();
        $loaded = new LoadedNamespace($fqsen, $namespace);

        foreach ($namespace->getInterfaces() as $fqsen => $interface) {
            if (!isExcluded($fqsen, false)) {
                $loaded->interfaces[$fqsen] = $this->loader->getInterface($fqsen);
            }
        }
        ksort($loaded->interfaces);

        foreach ($namespace->getClasses() as $fqsen => $class) {
            if (!isExcluded($fqsen, false)) {
                $loaded->classes[$fqsen] = $this->loader->getClass($fqsen);
            }
        }
        ksort($loaded->classes);

        foreach ($namespace->getTraits() as $fqsen => $trait) {
            if (!isExcluded($fqsen, false)) {
                $loaded->traits[$fqsen] = $this->loader->getTrait($fqsen);
            }
        }
        ksort($loaded->traits);

        return $loaded;
    }

    /**
     * Add missing parent namespaces.
     *
     * @param string $fqsen Namespace fqsen
     * @param array $namespaces Namespaces
     * @return void
     */
    protected function addMissingParents(string $fqsen, array &$namespaces): void
    {
        if ($fqsen === Configure::read('namespace')) {
            return;
        }

        $parent = substr($fqsen, 0, strrpos($fqsen, '\\'));
        if (isset($namespaces[$parent])) {
            return;
        }

        $this->log('Adding missing namespace: ' . $parent, 'info');
        $namespaces[$parent] = $this->loadNamespace(new Namespace_(new Fqsen($parent)));
        $this->addMissingParents($parent, $namespaces);
    }
}
