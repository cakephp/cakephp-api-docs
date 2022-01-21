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

use Cake\ApiDocs\Reflection\LoadedNamespace;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Composer\Autoload\ClassLoader;

class Project
{
    use LogTrait;

    public LoadedNamespace $global;

    public LoadedNamespace $root;

    protected Loader $loader;

    /**
     * Loads projects.
     *
     * @param string $projectPath Project path
     */
    public function __construct(string $projectPath)
    {
        $this->global = new LoadedNamespace(null, null);
        $this->root = new LoadedNamespace(Configure::read('root'), null);
        $this->loader = new Loader($this->findClassLoader($projectPath));

        $nodes = $this->loader->loadDirectory($projectPath);
        foreach ($nodes as $node) {
            if ($node->context->namespace) {
                $this->root->find($node->context->namespace)->add($node);
            } else {
                $this->global->add($node);
            }
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
}
