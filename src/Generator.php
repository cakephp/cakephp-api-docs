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
     */
    public function __construct(string $projectPath)
    {
        $this->project = new Project($projectPath);
        $this->renderer = new TwigRenderer(Configure::read('output'));
    }

    /**
     * Generates all files.
     *
     * @return void
     */
    public function generateAll(): void
    {
        $this->generateOverview();
        $this->generateNamespaces();
        $this->generateInterfaces();
        $this->generateClasses();
        $this->generateTraits();
        $this->generateSearch();
    }

    /**
     * Generate overview page with globals.
     *
     * @return void
     */
    public function generateOverview(): void
    {
        $constants = [];
        $functions = [];
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getConstants() as $constant) {
                $constants[] = new LoadedConstant($constant->getName(), (string)$constant->getFqsen(), '\\', $constant);
            }
            foreach ($file->file->getFunctions() as $function) {
                $functions[] = new LoadedFunction($function->getName(), (string)$function->getFqsen(), '\\', $function);
            }
        }

        $namespaces = $this->project->getProjectNamespaces();
        $this->renderer->render(
            'overview.twig',
            'index.html',
            ['constants' => $constants, 'functions' => $functions, 'namespaces' => $namespaces]
        );
    }

    /**
     * Geneate all namespaces.
     *
     * @return void
     */
    public function generateNamespaces(): void
    {
        $namespaces = $this->project->getProjectNamespaces();
        $renderNested = function ($loaded, $renderNested) use ($namespaces) {
            $filename = 'namespace-' . str_replace('\\', '.', substr($loaded->fqsen, 1)) . '.html';

            $this->renderer->render(
                'namespace.twig',
                $filename,
                ['loaded' => $loaded, 'namespaces' => $namespaces]
            );

            foreach ($loaded->children as $fqsen => $child) {
                $renderNested($child, $renderNested);
            }
        };

        foreach ($this->project->getProjectNamespaces() as $loaded) {
            $renderNested($loaded, $renderNested);
        }
    }

    /**
     * Generate all interfaces.
     *
     * @return void
     */
    public function generateInterfaces(): void
    {
        $namespaces = $this->project->getProjectNamespaces();
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getInterfaces() as $fqsen => $interface) {
                $loaded = $this->project->getLoader()->getInterface($fqsen);
                $filename = 'interface-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';

                $this->renderer->render(
                    'interface.twig',
                    $filename,
                    ['loaded' => $loaded, 'namespaces' => $namespaces]
                );
            }
        }
    }

    /**
     * Generate all classes.
     *
     * @return void
     */
    public function generateClasses(): void
    {
        $namespaces = $this->project->getProjectNamespaces();
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getClasses() as $fqsen => $class) {
                $loaded = $this->project->getLoader()->getClass($fqsen);
                $filename = 'class-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';

                $this->renderer->render(
                    'class.twig',
                    $filename,
                    ['loaded' => $loaded, 'namespaces' => $namespaces]
                );
            }
        }
    }

    /**
     * Generate all traits.
     *
     * @return void
     */
    public function generateTraits(): void
    {
        $namespaces = $this->project->getProjectNamespaces();
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getTraits() as $fqsen => $trait) {
                $loaded = $this->project->getLoader()->getTrait($fqsen);
                $filename = 'trait-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';

                $this->renderer->render(
                    'trait.twig',
                    $filename,
                    ['loaded' => $loaded, 'namespaces' => $namespaces]
                );
            }
        }
    }

    /**
     * Geneate a single interface, class or trait.
     *
     * @param string $fqsen The interface, class or trait fqsen
     * @param string $type One of the types listed for fqsen
     * @return void
     */
    public function generateSingle(string $fqsen, string $type): void
    {
        $namespaces = $this->project->getProjectNamespaces();

        $loaded = $this->project->getLoader()->{'get' . $type}($fqsen);
        $filename = $type . '-' . str_replace('\\', '.', substr($fqsen, 1)) . '.html';

        $this->renderer->render(
            $type . '.twig',
            $filename,
            ['loaded' => $loaded, 'namespaces' => $namespaces]
        );
    }

    /**
     * Generates search data.
     *
     * @return void
     */
    public function generateSearch(): void
    {
        $search = [];
        foreach ($this->project->getProjectFiles() as $file) {
            foreach ($file->file->getInterfaces() as $interface) {
                foreach (array_keys($interface->getConstants()) as $fqsen) {
                    $search[] = ['i', substr($fqsen, 1)];
                }
                foreach (array_keys($interface->getMethods()) as $fqsen) {
                    $search[] = ['i', substr($fqsen, 1)];
                }
            }
            foreach ($file->file->getClasses() as $class) {
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
