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

use Composer\Autoload\ClassLoader;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Namespace_;
use phpDocumentor\Reflection\Php\ProjectFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Finder
{
    /**
     * @var \Composer\Autoload\ClassLoader|null
     */
    protected $autoload;

    /**
     * @var \phpDocumentor\Reflection\Php\ProjectFactory
     */
    protected $factory;

    /**
     * @var \phpDocumentor\Reflection\Php\File[]
     */
    protected $files = [];

    /**
     * @var \phpDocumentor\Reflection\Php\File[]
     */
    protected $vendorFiles = [];

    /**
     * @param string $sourceDir Source directory
     * @param bool $searchVendor Whether to include files in vendor directory
     */
    public function __construct(string $sourceDir, bool $searchVendor = true)
    {
        if ($searchVendor) {
            $this->autoload = $this->loadAutoload($sourceDir);
        }

        $this->factory = ProjectFactory::createInstance();
        $this->files = $this->loadFiles($sourceDir);

        // Create empty namespace for namespaces that have no files
        $this->namespaces = $project->getNamespaces();
        foreach ($this->namespaces as $fqsen => $namespace) {
            $parentFqsen = substr($fqsen, 0, strrpos($fqsen, '\\'));
            if ($parentFqsen && empty($this->namespaces[$parentFqsen])) {
                $this->namespaces[$parentFqsen] = new Namespace_(new Fqsen($parentFqsen));
            }
        }
        ksort($this->namespaces);
    }

    /**
     * @param string $sourceDir source directory
     * @return \Composer\Autoload\ClassLoader|null
     */
    protected function findAutoload(string $sourceDir): ?ClassLoader
    {
        // try to find vendor/ relative to sourceDir
        $autoloadPath = $sourceDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (file_exists($autoloadPath)) {
            api_log('notice', "Found autoload at {$autoloadPath}.");
            $composer = require $autoloadPath;
            $composer->unregister();

            return $composer;
        }

        api_log('notice', 'No autoload found. Dependencies will not be parsed.');

        return null;
    }

    /**
     * @param string $sourceDir source directory
     * @return \phpDocumentor\Reflection\Php\File[]
     */
    protected function findFiles(string $sourceDir): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir)
        );

        $sourceFiles = [];
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            if (preg_match('/^.+\.php$/i', $entry->getFilename())) {
                $sourceFiles[] = new LocalFile(realpath((string)$entry));
            }
        }

        return $this->factory->create('project', $sourceFiles)->getFiles();
    }
}
