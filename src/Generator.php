<?php
declare(strict_types=1);

namespace Cake\ApiDocs;

use Cake\ApiDocs\Twig\Extension\ReflectionExtension;
use Cake\ApiDocs\Twig\TwigRenderer;
use Cake\ApiDocs\Util\SourceLoader;
use phpDocumentor\Reflection\Php\Namespace_;

class Generator
{
    /**
     * @var \Cake\ApiDocs\Util\SourceLoader
     */
    protected $loader;

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
            'fqsen' => (string)$namespace->getFqsen(),
            'namespace' => $namespace,
        ];
        $filename = 'namespace-' . str_replace('\\', '.', substr((string)$namespace->getFqsen(), 1)) . '.html';
        $this->renderer->render('namespace.twig', $filename, $context);
    }

    /*
    private function renderClassLike(ClassLikeContext $context): void
    {
        $fqsen = $context->getFqsen();
        $filename = 'class-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';

        $this->renderer->render('classlike.twig', $filename, [
            'fqsen' => $fqsen,
            'object' => $context,
        ]);
    }

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
