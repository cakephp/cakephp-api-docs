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
use Cake\ApiDocs\Reflection\Project;
use Cake\ApiDocs\Twig\TwigRenderer;
use Cake\Core\Configure;
use Cake\Log\LogTrait;

class Generator
{
    use LogTrait;

    /**
     * @var \Cake\ApiDocs\Reflection\Project
     */
    protected $project;

    /**
     * @var \Cake\ApiDocs\Twig\TwigRenderer
     */
    protected $renderer;

    /**
     * @param string $projectPath Project path
     * @param string $outputPath Html output path
     * @param string $templatePath Twig template path
     */
    public function __construct(string $projectPath, string $outputPath, string $templatePath)
    {
        $this->project = new Project($projectPath);

        $globals = [];
        foreach (['project', 'release', 'version', 'versions', 'namespace'] as $config) {
            $globals[$config] = Configure::read($config);
        }
        $this->renderer = new TwigRenderer($outputPath, $templatePath, $globals);
    }

    /**
     * Generates all files.
     *
     * @return void
     */
    public function generate(): void
    {
        $this->renderOverview();
        $this->renderNamespaces();
        $this->renderSearch();
    }

    /**
     * Renders overview page.
     *
     * @return void
     */
    public function renderOverview(): void
    {
        $namespaces = $this->project->getNamespaces();
        $this->renderer->render(
            'overview.twig',
            'index.html',
            ['namespaces' => $namespaces]
        );
    }

    /**
     * Render all namespaces and interfaces, classes and traits owned by interface.
     *
     * @return void
     */
    public function renderNamespaces(): void
    {
        $namespaces = $this->project->getNamespaces();
        $renderNested = function ($loaded, $renderNested) use ($namespaces) {
            // Render namespace
            $path = $loaded->fqsen === '\\' ? 'Global' : str_replace('\\', '.', substr($loaded->fqsen, 1));
            $filename = 'namespace-' . $path . '.html';
            $this->renderer->render(
                'namespace.twig',
                $filename,
                ['loaded' => $loaded, 'namespaces' => $namespaces]
            );

            // Render files owned by namespace
            $this->renderInterfaces($loaded);
            $this->renderClasses($loaded);
            $this->renderTraits($loaded);

            foreach ($loaded->children as $fqsen => $child) {
                $renderNested($child, $renderNested);
            }
        };

        foreach ($this->project->getNamespaces() as $loaded) {
            $renderNested($loaded, $renderNested);
        }
    }

    /**
     * Render all interfaces for namespace.
     *
     * @param \Cake\ApiDocs\Reflection\LoadedNamespace $loadedNamespace Loaded namespace
     * @return void
     */
    public function renderInterfaces(LoadedNamespace $loadedNamespace): void
    {
        foreach ($loadedNamespace->interfaces as $fqsen => $loadedInterface) {
            $filename = 'interface-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
            $this->renderer->render(
                'interface.twig',
                $filename,
                ['loaded' => $loadedInterface, 'namespaces' => $this->project->getNamespaces()]
            );
        }
    }

    /**
     * Render all classes for namespace.
     *
     * @param \Cake\ApiDocs\Reflection\LoadedNamespace $loadedNamespace Loaded namespace
     * @return void
     */
    public function renderClasses(LoadedNamespace $loadedNamespace): void
    {
        foreach ($loadedNamespace->classes as $fqsen => $loadedClass) {
            $filename = 'class-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
            $this->renderer->render(
                'class.twig',
                $filename,
                ['loaded' => $loadedClass, 'namespaces' => $this->project->getNamespaces()]
            );
        }
    }

    /**
     * Render all traits for namespace.
     *
     * @param \Cake\ApiDocs\Reflection\LoadedNamespace $loadedNamespace Loaded namespace
     * @return void
     */
    public function renderTraits(LoadedNamespace $loadedNamespace): void
    {
        foreach ($loadedNamespace->traits as $fqsen => $loadedTrait) {
            $filename = 'trait-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
            $this->renderer->render(
                'trait.twig',
                $filename,
                ['loaded' => $loadedTrait, 'namespaces' => $this->project->getNamespaces()]
            );
        }
    }

    /**
     * Renders search data.
     *
     * @return void
     */
    public function renderSearch(): void
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
