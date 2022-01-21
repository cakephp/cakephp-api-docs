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
use Cake\Log\LogTrait;
use Twig\Environment;

class Generator
{
    use LogTrait;

    /**
     * @var \Twig\Environment
     */
    protected Environment $twig;

    protected string $outputDir;

    /**
     * @param \Twig\Environment $twig Twig environment
     * @param string $outputDir Output directory
     */
    public function __construct(Environment $twig, string $outputDir)
    {
        $this->twig = $twig;
        $this->outputDir = $outputDir;
    }

    /**
     * Render project.
     *
     * @param \Cake\ApiDocs\Project $project Project
     * @return void
     */
    public function generate(Project $project): void
    {
        $this->log(sprintf('Generating project into `%s`', $this->outputDir), 'info');

        $namespaces = [];
        if (!$project->globalNamespace->isEmpty()) {
            $namespaces[] = $project->globalNamespace;
        }
        $namespaces[] = $project->rootNamespace;
        $this->twig->addGlobal('namespaces', $namespaces);

        $this->renderTemplate('pages/overview.twig', 'index.html');
        foreach ($namespaces as $namespace) {
            $this->renderNamespace($namespace);
        }
        /*
        $this->renderSearch();
        */
    }

    /**
     * Render all child namespaces, classes, interfaces and traits in namespace.
     *
     * @param \Cake\ApiDocs\ProjectNamespace $ns Project namespace
     * @return void
     */
    public function renderNamespace(ProjectNamespace $ns): void
    {
        $renderer = function (ProjectNamespace $ns) use (&$renderer): void {
            $this->renderTemplate(
                'pages/namespace.twig',
                sprintf('namespace-%s.html', str_replace('\\', '.', $ns->name ?? $ns->displayName)),
                ['namespace' => $ns, 'contextName' => $ns->name]
            );

            array_map(fn($interface) => $this->renderClassLike($interface, 'interface'), $ns->interfaces);
            array_map(fn($class) => $this->renderClassLike($class, 'class'), $ns->classes);
            array_map(fn($trait) => $this->renderClassLike($trait, 'trait'), $ns->traits);

            foreach ($ns->children as $child) {
                $renderer($child);
            }
        };

        $renderer($ns);
    }

    /**
     * Renders class, interface or trait.
     *
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $ref Reflected classlike
     * @param string $type Class-like type
     * @return void
     */
    protected function renderClassLike(ReflectedClassLike $ref, string $type): void
    {
        $filename = sprintf('%s-%s.html', $type, str_replace('\\', '.', $ref->qualifiedName()));
        $this->renderTemplate(
            'pages/classlike.twig',
            $filename,
            ['ref' => $ref, 'type' => $type, 'contextName' => $ref->context->namespace]
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

    /**
     * @param string $template Twig template name
     * @param string $filename Output filename
     * @param array $context Twig render context
     * @return void
     */
    protected function renderTemplate(string $template, string $filename, array $context = []): void
    {
        $path = getcwd() . DS . $this->outputDir . DS . $filename;
        file_put_contents($path, $this->twig->render($template, $context));
    }
}
