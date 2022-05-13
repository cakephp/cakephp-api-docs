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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ApiDocs;

use Cake\ApiDocs\Reflection\ReflectedClass;
use Cake\ApiDocs\Reflection\ReflectedClassLike;
use Cake\ApiDocs\Reflection\ReflectedInterface;
use Cake\ApiDocs\Util\MergeUtil;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\LogTrait;
use Composer\Autoload\ClassLoader;
use RuntimeException;

class Project
{
    use InstanceConfigTrait;

    use LogTrait;

    public ProjectNamespace $globalNamespace;

    public ProjectNamespace $rootNamespace;

    protected Loader $loader;

    protected array $_defaultConfig = [];

    /**
     * Loads projects.
     *
     * @param string $projectPath Project path
     * @param array $config Project config
     */
    public function __construct(string $projectPath, array $config)
    {
        $this->setConfig($config);

        $this->globalNamespace = new ProjectNamespace(null, 'Global');
        $this->rootNamespace = new ProjectNamespace($this->_config['namespace'], $this->_config['namespace']);

        $this->loader = new Loader($this->createClassLoader($projectPath));

        foreach ($this->_config['sourceDirs'] as $dir) {
            $this->log(sprintf('Loading sources from `%s`', $dir), 'info');
            $nodes = $this->loader->loadDirectory($projectPath . DIRECTORY_SEPARATOR . $dir);

            foreach ($nodes as $node) {
                if ($node instanceof ReflectedClassLike) {
                    $node->inProject = true;
                }

                $namespace = $this->findNamespace($node->context->namespace);
                $namespace->addNode($node);
            }
        }

        $this->mergeInherited();
    }

    /**
     * @return void
     */
    protected function mergeInherited(): void
    {
        $classLikeMerger = function (ReflectedClassLike $ref) use (&$classLikeMerger) {
            foreach ($ref->uses as $use) {
                $trait = $this->loader->find($ref->context->resolveClassLike($use));
                if (!$trait) {
                    continue;
                }
                $classLikeMerger($trait);
                MergeUtil::mergeClassLike($ref, $trait);
            }

            if ($ref instanceof ReflectedClass) {
                foreach ($ref->implements as $implement) {
                    $interface = $this->loader->find($ref->context->resolveClassLike($implement));
                    if (!$interface) {
                        continue;
                    }
                    $classLikeMerger($interface);
                    MergeUtil::mergeClassLike($ref, $interface);
                }
            }

            if ($ref instanceof ReflectedInterface || $ref instanceof ReflectedClass) {
                foreach ((array)$ref->extends ?? [] as $extend) {
                    $interface = $this->loader->find($ref->context->resolveClassLike($extend));
                    if (!$interface) {
                        continue;
                    }
                    $classLikeMerger($interface);
                    MergeUtil::mergeClassLike($ref, $interface);
                }
            }
        };

        $namespaceMerger = function (ProjectNamespace $ns) use (&$namespaceMerger, $classLikeMerger) {
            foreach ($ns->children as $child) {
                $namespaceMerger($child);
            }

            foreach ($ns->interfaces as $interface) {
                $classLikeMerger($interface);
            }
            foreach ($ns->traits as $trait) {
                $classLikeMerger($trait);
            }
            foreach ($ns->classes as $class) {
                $classLikeMerger($class);
            }
        };

        $namespaceMerger($this->globalNamespace);
        $namespaceMerger($this->rootNamespace);
    }

    /**
     * @param string $projectPath Project path
     * @return \Composer\Autoload\ClassLoader|null
     */
    protected function createClassLoader(string $projectPath): ?ClassLoader
    {
        // try to find vendor/ relative to sourceDir
        $autoloadPath = $projectPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!file_exists($autoloadPath)) {
            $this->log("Unable to find class loader at `$autoloadPath`", 'warning');

            return null;
        }

        $this->log("Found class loader at `$autoloadPath`", 'info');
        $loader = require $autoloadPath;
        $loader->unregister();

        return $loader;
    }

    /**
     * Finds or creates project namespace.
     *
     * @param string|null $name Namespace name
     * @return self|null
     */
    protected function findNamespace(?string $name): ProjectNamespace
    {
        if ($name === null) {
            return $this->globalNamespace;
        }

        if (!str_starts_with($name, $this->rootNamespace->name)) {
            throw new RuntimeException(sprintf(
                'Namespace `%s` is not a child of the project root `%s`.',
                $name,
                $this->rootNamespace->name
            ));
        }

        $ns = $this->rootNamespace;
        $parts = explode('\\', substr($name, strlen($this->rootNamespace->name)));
        if (count($parts) > 1) {
            array_shift($parts);
        }

        foreach ($parts as $part) {
            if (isset($ns->children[$part])) {
                $ns = $ns->children[$part];
                continue;
            }

            $ns->children[$part] = new ProjectNamespace($ns->name . '\\' . $part, $part);
            ksort($ns->children);

            $ns = $ns->children[$part];
        }

        return $ns;
    }
}
