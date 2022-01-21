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

use Cake\ApiDocs\Reflection\LoadedClass;
use Cake\ApiDocs\Reflection\LoadedClassLike;
use Cake\ApiDocs\Reflection\LoadedInterface;
use Cake\ApiDocs\Reflection\LoadedNamespace;
use Cake\ApiDocs\Reflection\LoadedTrait;
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
        $this->renderNamespaceTree($this->project->global);
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
    public function renderOverview(): void
    {
        $this->renderer->render('overview.twig', 'index.html');
    }

    /**
     * Render all namespaces, classes, interfaces and traits in namespace tree.
     *
     * @param \Cake\ApiDocs\Reflection\LoadedNamespace $tree Loaded namespace tree
     * @return void
     */
    public function renderNamespaceTree(LoadedNamespace $tree): void
    {
        $filename = sprintf('namespace-%s.html', preg_replace('[\\\\]', '.', $tree->namespaced() ?: 'Global'));
        $this->renderer->render('namespace.twig', $filename, ['namespace' => $tree]);

        foreach ($tree->interfaces as $interface) {
            $this->renderClassLike($interface);
        }
        foreach ($tree->classes as $class) {
            $this->renderClassLike($class);
        }
        foreach ($tree->traits as $trait) {
            $this->renderClassLike($trait);
        }

        foreach ($tree->children as $child) {
            $this->renderNamespaceTree($child);
        }
    }

    /**
     * Render all interfaces for namespace.
     *
     * @param \Cake\ApiDocs\Reflection\LoadedClassLike $classLike Loaded class-like
     * @return void
     */
    public function renderClassLike(LoadedClassLike $classLike): void
    {
        $type = match (true) {
            $classLike instanceof LoadedInterface => 'interface',
            $classLike instanceof LoadedClass => 'class',
            $classLike instanceof LoadedTrait => 'trait',
        };

        $filename = sprintf('%s-%s.html', $type, str_replace('\\', '.', $classLike->namespaced()));
        $this->renderer->render('classlike.twig', $filename, ['classLikeType' => $type, 'classLike' => $classLike]);
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
