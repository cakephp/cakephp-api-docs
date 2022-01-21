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

use Cake\ApiDocs\Reflection\ReflectedNamespace;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Composer\Autoload\ClassLoader;
use RuntimeException;

class Project
{
    use LogTrait;

    public ?ReflectedNamespace $global = null;

    public ReflectedNamespace $root;

    protected Loader $loader;

    /**
     * Loads projects.
     *
     * @param string $projectPath Project path
     */
    public function __construct(string $projectPath)
    {
        $this->root = new ReflectedNamespace(Configure::read('root'), null);
        $this->loader = new Loader($this->findClassLoader($projectPath));

        $nodes = $this->loader->loadDirectory($projectPath);
        foreach ($nodes as $node) {
            $namespace = $this->findOrCreateNamespace($node->context->namespace);
            $namespace->addNode($node);
        }
    }

    /**
     * @param string $path Project path
     * @return \Composer\Autoload\ClassLoader
     */
    protected function findClassLoader(string $path): ClassLoader
    {
        // try to find vendor/ relative to sourceDir
        $autoloadPath = $path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new RuntimeException("Unable to find class loader at `$autoloadPath`.");
        }

        $this->log("Found class loader at `$autoloadPath`", 'info');
        $loader = require $autoloadPath;
        $loader->unregister();

        return $loader;
    }

    /**
     * Finds or creates reflected namespace.
     *
     * @param string|null $qualifiedName Qualified name
     * @return self|null
     */
    public function findOrCreateNamespace(?string $qualifiedName): ReflectedNamespace
    {
        if ($qualifiedName === null) {
            return $this->global ?? ($this->global = new ReflectedNamespace(null, null));
        }

        if ($this->root->qualifiedName === $qualifiedName) {
            return $this->root;
        }

        if (!str_starts_with($qualifiedName, $this->root->qualifiedName)) {
            throw new RuntimeException(sprintf(
                'Namespace `%s` is not a child of the project root `%s`.',
                $qualifiedName,
                $this->root->qualifiedName
            ));
        }

        $parent = $this->root;
        $names = explode('\\', substr($qualifiedName, strlen($this->root->qualifiedName) + 1));
        while ($names) {
            $name = array_shift($names);
            if (!isset($parent->children[$name])) {
                $child = new ReflectedNamespace($parent->qualifiedName . '\\' . $name, $parent);
                $parent = $parent->children[$name] = $child;
            }
        }

        return $parent;
    }
}
