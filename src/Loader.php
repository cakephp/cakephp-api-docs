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
use FilesystemIterator;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Loader
{
    protected string $projectPath;

    protected Parser $parser;

    protected NodeTraverser $traverser;

    /**
     * @param string $projectPath Project path
     */
    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @param string $path Directory path
     * @param bool $inProject Whether directory is within project
     * @return array
     */
    public function loadDirectory(string $path, bool $inProject): array
    {
        $nodes = [];
        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME
        );
        foreach (new RecursiveIteratorIterator($directoryIterator) as $filePath) {
            if (preg_match('/\.php$/', $filePath)) {
                $nodes = array_merge($nodes, $this->loadFile($filePath, $inProject));
            }
        }

        return $nodes;
    }

    /**
     * @param string $path File path
     * @param bool $inProject Whether file is within project
     * @return array<string, \Cake\ApiDocs\Reflection\LoadedNode>
     */
    public function loadFile(string $path, bool $inProject): array
    {
        $stmts = $this->parser->parse(file_get_contents($path));

        $traverser = new NodeTraverser();
        $visitor = new FileVisitor(new Factory(), substr($path, strlen($this->projectPath) + 1), $inProject);
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
}
