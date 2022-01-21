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

use Cake\ApiDocs\Reflection\LoadedClassLike;
use Cake\Core\Configure;
use Composer\Autoload\ClassLoader;
use FilesystemIterator;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Loader
{
    protected Parser $parser;

    protected NodeTraverser $traverser;

    protected ?ClassLoader $classLoader;

    protected array $cache = [];

    /**
     * @param \Composer\Autoload\ClassLoader|null $classLoader Class loader
     */
    public function __construct(?ClassLoader $classLoader)
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->classLoader = $classLoader;
    }

    /**
     * @param string $path Directory path
     * @return array
     */
    public function loadDirectory(string $path): array
    {
        $nodes = [];
        foreach (Configure::read('sourceDirs') as $sourcePath) {
            $diretoryIterator = new RecursiveDirectoryIterator(
                $path . DIRECTORY_SEPARATOR . $sourcePath,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME
            );
            $iterator = new RecursiveIteratorIterator($diretoryIterator);
            foreach ($iterator as $filePath) {
                if (preg_match('/\.php$/', $filePath)) {
                    $nodes = array_merge($nodes, $this->loadFile($filePath));
                }
            }
        }

        return $nodes;
    }

    /**
     * @param string $path File path
     * @return array<string, \Cake\ApiDocs\Reflection\LoadedNode>
     */
    public function loadFile(string $path): array
    {
        $stmts = $this->parser->parse(file_get_contents($path));

        $traverser = new NodeTraverser();
        $visitor = new FileVisitor(new Factory($path));
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        $nodes = $visitor->getNodes();
        /*
        foreach ($nodes as $node) {
            if ($node instanceof LoadedClassLike) {
                $this->cache[$node->qualifyName()] = $node;
            }
        }
        */

        return $nodes;
    }

    /**
     * @param string $classLike Qualified name
     * @return \Cake\ApiDocs\Reflection\LoadedClassLike|null
     */
    public function find(string $classLike): ?LoadedClassLike
    {
        if (array_key_exists($classLike, $this->cache)) {
            return $this->cache[$classLike];
        }

        $path = $this->classLoader->findFile($classLike);
        if ($path === false) {
            return null;
        }

        $nodes = $this->loadFile($path);
        foreach ($nodes as $node) {
            if ($node instanceof ReflectedClassLike && $node->qualifiedName === $classLike) {
                return $node;
            }
        }

        return $this->cache[$classLike] = null;
    }
}
