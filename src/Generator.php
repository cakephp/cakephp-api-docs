<?php
declare(strict_types=1);

namespace Cake\ApiDocs;

use Cake\ApiDocs\Twig\Extension\ReflectionExtension;
use Cake\ApiDocs\Twig\TwigRenderer;
use Cake\ApiDocs\Util\ClassLikeCollapser;
use Cake\ApiDocs\Util\SourceLoader;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Php\Namespace_;

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
        foreach ($this->loader->getFiles() as $file) {
            foreach ($file->getClasses() as $class) {
                $this->renderClassLike('class', $class);
            }
            foreach ($file->getInterfaces() as $interface) {
                $this->renderClassLike('interface', $interface);
            }
            foreach ($file->getTraits() as $trait) {
                $this->renderClassLike('trait', $trait);
            }
        }
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
     * @param \phpDocumentor\Reflection\Php\Class_|\phpDocumentor\Reflection\Php\Interface_|\phpDocumentor\Reflection\Php\Trait_ $classlike classlike
     * @return void
     */
    protected function renderClassLike(string $type, Element $classlike): void
    {
        $context = [
            'type' => $type,
            'classlike' => $classlike,
            'collapsed' => $this->collapser->collapse($classlike),
        ];
        $filename = $this->getFilename($type, (string)$classlike->getFqsen());
        $this->renderer->render('classlike.twig', $filename, $context);
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
    private function renderSearch(array $namespaces): void
    {
        $elements = [];
        foreach ($namespaces as $namespace) {
            foreach (['constants', 'properties', 'methods'] as $attribute) {
                foreach (['classes', 'traits'] as $type) {
                    foreach ($namespace->{'get' . $type}() as $class) {
                        foreach ($class->{'get' . $attribute}() as $element) {
                            $elements[] = ['c', substr($element->getFqsen(), 1)];
                        }
                    }
                }
            }
            foreach ($namespace->getInterfaces() as $interface) {
                foreach ($interface->getConstants() as $element) {
                    $elements[] = ['c', substr($element->getFqsen(), 1)];
                }
            }
        }
        $this->renderer->render('searchlist.twig', 'searchlist.js', ['elements' => json_encode($elements)]);
    }

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
