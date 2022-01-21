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
use Cake\ApiDocs\Reflection\ReflectedInterface;
use Cake\ApiDocs\Reflection\ReflectedNamespace;
use Cake\ApiDocs\Reflection\ReflectedTrait;
use Cake\ApiDocs\Twig\TwigRenderer;
use Cake\Core\Configure;
use Cake\Log\LogTrait;

class Generator
{
    use LogTrait;

    protected string $projectPath;

    protected string $outputPath;

    /**
     * @var \Cake\ApiDocs\Project
     */
    protected $project;

    /**
     * @var \Cake\ApiDocs\Twig\TwigRenderer
     */
    protected $renderer;

    /**
     * @param string $projectPath Project path
     * @param string $outputPath Html output path
     */
    public function __construct(string $projectPath, string $outputPath)
    {
        $this->projectPath = $projectPath;

        $this->outputPath = $outputPath;
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        if (!is_dir($this->outputPath)) {
            throw new InvalidArgumentException("Unable to create output directory `{$this->outputPath}`.");
        }

        $this->project = new Project($this->projectPath);
        $this->renderer = new TwigRenderer($outputPath, Configure::read('templatePath'));
        $this->renderer->addGlobal('config', Configure::read());
        $this->renderer->addGlobal('project', $this->project);
    }

    /**
     * Render all files.
     *
     * @return void
     */
    public function render(): void
    {
        $this->renderOverview();

        if ($this->project->global) {
            $this->renderNamespaceTree($this->project->global);
        }
        $this->renderNamespaceTree($this->project->root);
        /*
        $this->renderSearch();
        */
    }

    /**
     * Render overview page.
     *
     * @return void
     */
    protected function renderOverview(): void
    {
        $this->renderer->render('pages/overview.twig', 'index.html');
    }

    /**
     * Render all namespaces, classes, interfaces and traits in namespace tree.
     *
     * @param \Cake\ApiDocs\Reflection\ReflectedNamespace $root Root namespace node
     * @return void
     */
    public function renderNamespaceTree(ReflectedNamespace $root): void
    {
        $renderer = function (ReflectedNamespace $ref) use (&$renderer): void {
            $filename = sprintf('namespace-%s.html', str_replace('\\', '.', $ref->qualifiedName ?: 'Global'));
            $this->renderer->render(
                'pages/namespace.twig',
                $filename,
                ['namespace' => $ref]
            );

            array_map(fn($interface) => $this->renderInterface($interface, $ref), $ref->interfaces);
            array_map(fn($class) => $this->renderClass($class, $ref), $ref->classes);
            array_map(fn($trait) => $this->renderTrait($trait, $ref), $ref->traits);

            foreach ($ref->children as $child) {
                $renderer($child);
            }
        };

        $renderer($root);
    }

    /**
     * Renders interface.
     *
     * @param \Cake\ApiDocs\Reflection\ReflectedInterface $ref Reflected interface
     * @param \Cake\ApiDocs\Reflection\ReflectedNamespace $namespace Reflected namespace
     * @return void
     */
    protected function renderInterface(ReflectedInterface $ref, ReflectedNamespace $namespace): void
    {
        $filename = sprintf('interface-%s.html', str_replace('\\', '.', $ref->qualifiedName));
        $this->renderer->render(
            'pages/interface.twig',
            $filename,
            ['classLike' => $ref, 'namespace' => $namespace]
        );
    }

    /**
     * Renders interface.
     *
     * @param \Cake\ApiDocs\Reflection\ReflectedClass $ref Reflected class
     * @param \Cake\ApiDocs\Reflection\ReflectedNamespace $namespace Reflected namespace
     * @return void
     */
    protected function renderClass(ReflectedClass $ref, ReflectedNamespace $namespace): void
    {
        $filename = sprintf('class-%s.html', str_replace('\\', '.', $ref->qualifiedName));
        $this->renderer->render(
            'pages/class.twig',
            $filename,
            ['classLike' => $ref, 'namespace' => $namespace]
        );
    }

    /**
     * Renders interface.
     *
     * @param \Cake\ApiDocs\Reflection\ReflectedTrait $ref Reflected trait
     * @param \Cake\ApiDocs\Reflection\ReflectedNamespace $namespace Reflected namespace
     * @return void
     */
    protected function renderTrait(ReflectedTrait $ref, ReflectedNamespace $namespace): void
    {
        $filename = sprintf('trait-%s.html', str_replace('\\', '.', $ref->qualifiedName));
        $this->renderer->render(
            'pages/trait.twig',
            $filename,
            ['classLike' => $ref, 'namespace' => $namespace]
        );
    }

    /**
     * Renders search data.
     *
     * @return void
     */
    protected function renderSearch(): void
    {
        $search = [];
        $addNested = function ($loaded, $addNested) use (&$search) {
            foreach ($loaded->children as $child) {
                $addNested($child, $addNested);
            }

            // Add interface entries
            foreach ($loaded->interfaces as $loadedInterface) {
                foreach ($loadedInterface->constants as $loadedConstant) {
                    $search[] = ['i', substr($loadedConstant->fqsen, 1)];
                }
                foreach ($loadedInterface->methods as $loadedMethod) {
                    $search[] = ['i', substr($loadedMethod->fqsen, 1)];
                }
            }

            // Add class entries
            foreach ($loaded->classes as $loadedClass) {
                foreach ($loadedClass->constants as $loadedConstant) {
                    $search[] = ['c', substr($loadedConstant->fqsen, 1)];
                }
                /* skip properties to reduce search results
                foreach ($loadedClass->properties as $loadedProperty) {
                    $search[] = ['c', substr($loadedProperty->fqsen, 1)];
                }
                */
                foreach ($loadedClass->methods as $loadedMethod) {
                    $search[] = ['c', substr($loadedMethod->fqsen, 1)];
                }
            }

            // Add trait entries
            foreach ($loaded->traits as $loadedTrait) {
                /* skip properties to reduce search results
                foreach ($loadedTrait->properties as $loadedProperty) {
                    $search[] = ['t', substr($loadedProperty->fqsen, 1)];
                }
                */
                foreach ($loadedTrait->methods as $loadedMethod) {
                    $search[] = ['t', substr($loadedMethod->fqsen, 1)];
                }
            }
        };

        foreach ($this->project->getNamespaces() as $loaded) {
            $addNested($loaded, $addNested);
        }

        $this->renderer->render('searchlist.twig', 'searchlist.js', ['entries' => json_encode(array_values($search))]);
    }
}
