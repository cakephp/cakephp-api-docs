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

use Cake\ApiDocs\Reflection\LoadedConstant;
use Cake\ApiDocs\Reflection\LoadedFunction;
use Cake\ApiDocs\Reflection\LoadedNamespace;
use Cake\ApiDocs\Reflection\Project;
use Cake\ApiDocs\Twig\TwigRenderer;
use Cake\Core\Configure;

class Generator
{
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
        $constants = [];
        $functions = [];
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getConstants() as $constant) {
                $constants[$constant->getName()] = new LoadedConstant(
                    $constant->getName(),
                    (string)$constant->getFqsen(),
                    '\\',
                    $constant
                );
            }
            foreach ($file->file->getFunctions() as $function) {
                $functions[$function->getName()] = new LoadedFunction(
                    $function->getName(),
                    (string)$function->getFqsen(),
                    '\\',
                    $function
                );
            }
        }
        ksort($constants);
        ksort($functions);

        $namespaces = $this->project->getProjectNamespaces();
        $this->renderer->render(
            'overview.twig',
            'index.html',
            ['constants' => $constants, 'functions' => $functions, 'namespaces' => $namespaces]
        );
    }

    /**
     * Render all namespaces and interfaces, classes and traits owned by interface.
     *
     * @return void
     */
    public function renderNamespaces(): void
    {
        $namespaces = $this->project->getProjectNamespaces();
        $renderNested = function ($loaded, $renderNested) use ($namespaces) {
            if (isExcluded($loaded->fqsen, true)) {
                return;
            }

            // Render namespace
            $filename = 'namespace-' . str_replace('\\', '.', substr($loaded->fqsen, 1)) . '.html';
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

        foreach ($this->project->getProjectNamespaces() as $loaded) {
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
            if (isExcluded($fqsen, false)) {
                continue;
            }

            $filename = 'interface-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
            $this->renderer->render(
                'interface.twig',
                $filename,
                ['loaded' => $loadedInterface, 'namespaces' => $this->project->getProjectNamespaces()]
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
            if (isExcluded($fqsen, false)) {
                continue;
            }

            $filename = 'class-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
            $this->renderer->render(
                'class.twig',
                $filename,
                ['loaded' => $loadedClass, 'namespaces' => $this->project->getProjectNamespaces()]
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
            if (isExcluded($fqsen, false)) {
                continue;
            }

            $filename = 'trait-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';
            $this->renderer->render(
                'trait.twig',
                $filename,
                ['loaded' => $loadedTrait, 'namespaces' => $this->project->getProjectNamespaces()]
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
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getInterfaces() as $interface) {
                if (isExcluded((string)$interface->getFqsen(), false)) {
                    continue;
                }

                foreach (array_keys($interface->getConstants()) as $fqsen) {
                    $search[] = ['i', substr($fqsen, 1)];
                }
                foreach (array_keys($interface->getMethods()) as $fqsen) {
                    $search[] = ['i', substr($fqsen, 1)];
                }
            }
            foreach ($file->file->getClasses() as $class) {
                if (isExcluded((string)$class->getFqsen(), false)) {
                    continue;
                }

                foreach (array_keys($class->getConstants()) as $fqsen) {
                    $search[] = ['c', substr($fqsen, 1)];
                }
                foreach (array_keys($class->getConstants()) as $fqsen) {
                    $search[] = ['c', substr($fqsen, 1)];
                }
                foreach (array_keys($class->getMethods()) as $fqsen) {
                    $search[] = ['c', substr($fqsen, 1)];
                }
            }
            foreach ($file->file->getTraits() as $trait) {
                if (isExcluded((string)$trait->getFqsen(), false)) {
                    continue;
                }

                foreach (array_keys($trait->getProperties()) as $fqsen) {
                    $search[] = ['t', substr($fqsen, 1)];
                }
                foreach (array_keys($trait->getMethods()) as $fqsen) {
                    $search[] = ['t', substr($fqsen, 1)];
                }
            }
        }

        $this->renderer->render('searchlist.twig', 'searchlist.js', ['entries' => json_encode(array_values($search))]);
    }
}
