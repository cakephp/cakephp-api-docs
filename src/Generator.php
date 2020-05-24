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

use Cake\ApiDocs\Twig\Extension\ReflectionExtension;
use Cake\ApiDocs\Twig\TwigRenderer;
use Cake\ApiDocs\Util\ClassLikeCollapser;
use Cake\ApiDocs\Util\CollapsedClassLike;
use Cake\ApiDocs\Util\SourceLoader;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Namespace_;
use phpDocumentor\Reflection\Php\Trait_;

class Generator
{
    /**
     * @var \Cake\ApiDocs\Util\SourceLoader
     */
    protected $loader;

    /**
     * @var \Cake\ApiDocs\Util\ClassLikeCollapser
     */
    protected $collapser;

    /**
     * @var \Cake\ApiDocs\Twig\TwigRenderer
     */
    protected $renderer;

    /**
     * @param \Cake\ApiDocs\Util\SourceLoader $loader source loader
     * @param array $config config
     */
    public function __construct(SourceLoader $loader, array $config)
    {
        $this->loader = $loader;
        $this->collapser = new ClassLikeCollapser($loader);
        $this->renderer = new TwigRenderer($config['templates'], $config['output']);
        $this->renderer->getTwig()->addExtension(new ReflectionExtension($loader));
        $this->renderer->getTwig()->addGlobal('config', $config['globals']);
    }

    /**
     * @return void
     */
    public function generate(): void
    {
        $this->renderOverview();
        foreach ($this->loader->getNamespaces() as $namespace) {
            $this->renderNamespace($namespace);
        }

        $all = [];
        foreach ($this->loader->getFiles() as $file) {
            foreach ($file->getClasses() as $class) {
                $collapsed = $this->collapser->collapse($class);
                $this->renderClassLike('class', $collapsed);
                $all[] = $collapsed;
            }
            foreach ($file->getInterfaces() as $interface) {
                $collapsed = $this->collapser->collapse($interface);
                $this->renderClassLike('interface', $collapsed);
                $all[] = $collapsed;
            }
            foreach ($file->getTraits() as $trait) {
                $collapsed = $this->collapser->collapse($trait);
                $this->renderClassLike('trait', $collapsed);
                $all[] = $collapsed;
            }
        }

        $this->renderSearch($all);
    }

    /**
     * @return void
     */
    protected function renderOverview(): void
    {
        $functions = [];
        $constants = [];
        foreach ($this->loader->getFiles() as $file) {
            $functions = array_merge($functions, array_keys($file->getFunctions()));
            $constants = array_merge($constants, array_keys($file->getConstants()));
        }
        sort($functions);
        sort($constants);

        $context = [
            'namespaces' => array_keys($this->loader->getNamespaces()),
            'functions' => $functions,
            'constants' => $constants,
        ];
        $this->renderer->render('overview.twig', 'index.html', $context);
    }

    /**
     * @param \phpDocumentor\Reflection\Php\Namespace_ $namespace namesapce
     * @return void
     */
    protected function renderNamespace(Namespace_ $namespace): void
    {
        $context = [
            'namespace' => $namespace,
        ];
        $filename = $this->getFilename('namespace', (string)$namespace->getFqsen());
        $this->renderer->render('namespace.twig', $filename, $context);
    }

    /**
     * @param string $type file type
     * @param \Cake\ApiDocs\Util\CollapsedClassLike $collapsed collapsed classlike
     * @return void
     */
    protected function renderClassLike(string $type, CollapsedClassLike $collapsed): void
    {
        $context = [
            'type' => $type,
            'collapsed' => $collapsed,
        ];
        $filename = $this->getFilename($type, $collapsed->getSource()->getFqsen());
        $this->renderer->render('classlike.twig', $filename, $context);
    }

    /**
     * @param \Cake\ApiDocs\Util\CollapsedClassLike[] $all all collapsed
     * @return void
     */
    protected function renderSearch(array $all): void
    {
        $entries = [];
        foreach ($all as $collapsed) {
            $source = $collapsed->getSource();
            foreach (['getConstants', 'getProperties', 'getMethods'] as $getter) {
                foreach ($collapsed->{$getter}() as $element) {
                    $declaration = $element['source'];

                    if ($declaration->getInProject()) {
                        $type = '';
                        if ($declaration->getParent() instanceof Class_) {
                            $type = 'c';
                        } elseif ($declaration->getParent() instanceof Interface_) {
                            $type = 'i';
                        } elseif ($declaration->getParent() instanceof Trait_) {
                            $type = 't';
                        }

                        $fqsen = substr($declaration->getFqsen(), 1);
                        $entries[$fqsen] = [$type, $fqsen];
                    }
                }
            }
        }
        $this->renderer->render('searchlist.twig', 'searchlist.js', ['entries' => json_encode(array_values($entries))]);
    }

    /**
     * @param string $type file type
     * @param string $fqsen fqsen
     * @return string
     */
    protected function getFilename(string $type, string $fqsen): string
    {
        return "{$type}-" . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
    }

    /*
    private function buildTree(array $namespaces): Node
    {
        $unique = [];
        foreach ($namespaces as $namespace) {
            $names = explode('\\', $namespace);
            foreach (range(2, count($names)) as $length) {
                $unique[] = implode('\\', array_slice($names, 0, $length));
            }
        }
        $unique = array_unique($unique);
        sort($unique);

        $nodes = [];
        foreach ($unique as $namespace) {
            $parent = substr($namespace, 0, strrpos($namespace, '\\'));
            $nodes[] = ['name' => $namespace, 'parent' => $parent];
        }
        $nodes = new Collection($nodes);
        $nodes = $nodes->nest('name', 'parent');

        return new Node();
    }

    private function addFilters(Loader $loader): void
    {
        $this->renderer->addFilter(new TwigFilter(
            'fqsen_url',
            function (string $fqsen, ?string $type = null) use ($loader) {
                $parts = explode('::', $fqsen);
                $objectType = $type;
                if ($objectType === null) {
                    $objectType = 'class';

                    [, $inProject] = $loader->getClass($parts[0]);
                    if (!$inProject) {
                        return '';
                    }
                }

                $url = $objectType . '-' . str_replace('\\', '.', substr($parts[0], 1)) . '.html';

                if (count($parts) > 1) {
                    $anchor = $parts[1];
                    if ($anchor[-1] === ')') {
                        $anchor = substr($anchor, 0, -2);
                    }
                    $url .= '#' . $anchor;
                }

                return $url;
            }
        ));

        $this->renderer->addFilter(new TwigFilter(
            'children',
            function (FqsenTree $tree, string $type) {
                return $tree->getChildrenByType($type);
            }
        ));
    }
    */
}
