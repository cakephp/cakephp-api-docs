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

use Cake\ApiDocs\Reflection\ReflectedClassLike;
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
        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME
        );
        foreach (new RecursiveIteratorIterator($directoryIterator) as $filePath) {
            if (preg_match('/\.php$/', $filePath)) {
                $nodes = array_merge($nodes, $this->loadFile($filePath));
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
        foreach ($nodes as $node) {
            if ($node instanceof ReflectedClassLike) {
                $this->cache[$node->qualifiedName()] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param string $qualifiedName Qualified name
     * @return \Cake\ApiDocs\Reflection\ReflectedClassLike|null
     */
    public function find(string $qualifiedName): ?ReflectedClassLike
    {
        if (array_key_exists($qualifiedName, $this->cache)) {
            return $this->cache[$qualifiedName];
        }

        if (!$this->classLoader) {
            return null;
        }

        $path = $this->classLoader->findFile($qualifiedName);
        if ($path === false) {
            return null;
        }

        $nodes = $this->loadFile($path);
        foreach ($nodes as $node) {
            if ($node instanceof ReflectedClassLike && $node->qualifiedName() === $qualifiedName) {
                return $node;
            }
        }

        return $this->cache[$qualifiedName] = null;
    }
}
