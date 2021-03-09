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
            $this->log("Loading project files from `$filesPath`.", 'info');

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

        $namespaces = [];
        foreach ($project->getNamespaces() as $fqsen => $namespace) {
            if (in_array($fqsen, Configure::read('excludes.namespaces', []), true)) {
                continue;
            }

            $namespaces[$fqsen] = new LoadedNamespace($fqsen, $namespace);
            foreach (array_keys($namespace->getInterfaces()) as $interfaceFqsen) {
                if (isExcluded($interfaceFqsen, false)) {
                    continue;
                }
                $namespaces[$fqsen]->interfaces[$interfaceFqsen] = $this->loader->getInterface($interfaceFqsen);
            }
            ksort($namespaces[$fqsen]->interfaces);

            foreach (array_keys($namespace->getClasses()) as $classFqsen) {
                if (isExcluded($classFqsen, false)) {
                    continue;
                }
                $namespaces[$fqsen]->classes[$classFqsen] = $this->loader->getClass($classFqsen);
            }
            ksort($namespaces[$fqsen]->classes);

            foreach (array_keys($namespace->getTraits()) as $traitFqsen) {
                if (isExcluded($traitFqsen, false)) {
                    continue;
                }
                $namespaces[$fqsen]->traits[$traitFqsen] = $this->loader->getTrait($traitFqsen);
            }
            ksort($namespaces[$fqsen]->traits);

            // Create empty namespace for namespaces that have no files
            if ($fqsen !== Configure::read('namespace')) {
                $parent = substr($fqsen, 0, strrpos($fqsen, '\\'));
                if (empty($project->getNamespaces()[$parent])) {
                    $namespaces[$parent] = new LoadedNamespace($parent, new Namespace_(new Fqsen($parent)));
                }
            }
        }
        ksort($namespaces);

        // Create nested array
        foreach ($namespaces as $fqsen => $namespace) {
            if ($fqsen === Configure::read('namespace')) {
                $this->projectNamespaces[$fqsen] = $namespace;
                continue;
            }

            $parent = substr($fqsen, 0, strrpos($fqsen, '\\'));
            $namespaces[$parent]->children[$fqsen] = $namespace;
        }
    }
}
