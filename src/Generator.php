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
use Cake\ApiDocs\Reflection\ReflectedInterface;
use Cake\ApiDocs\Reflection\ReflectedTrait;
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

        $this->twig->addGlobal('namespaces', $project->namespaces);

        $this->renderOverview();
        $this->renderSearch($project->namespaces);

        array_walk($project->namespaces, fn ($namespace) => $this->renderNamespace($namespace));
    }

    /**
     * Render overview page.
     *
     * @return void
     */
    public function renderOverview(): void
    {
        $this->renderTemplate('pages/overview.twig', 'index.html');
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
                sprintf('namespace-%s.html', str_replace('\\', '.', $ns->qualifiedName ?? $ns->name)),
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
     * @param array $namespaces Project namespaces
     * @return void
     */
    protected function renderSearch(array $namespaces): void
    {
        $entries = [];

        $addClassLike = function (ReflectedClassLike $classLike) use (&$entries) {
            $type = match (true) {
                $classLike instanceof ReflectedInterface => 'i',
                $classLike instanceof ReflectedTrait => 't',
                $classLike instanceof ReflectedClassLike => 'c'
            };

            $entries[] = [$type, $classLike->qualifiedName()];
            foreach ($classLike->constants as $constant) {
                if (
                    $constant->visibility === 'public' &&
                    (
                        !$constant->source->inProject || $constant->owner === $classLike
                    )
                ) {
                    $entries[] = [$type, sprintf('%s::%s', $classLike->qualifiedName(), $constant->name)];
                }
            }
            foreach ($classLike->properties as $property) {
                if (
                    $property->visibility === 'public' &&
                    (
                        !$property->source->inProject || $property->owner === $classLike
                    )
                ) {
                    $entries[] = [$type, sprintf('%s::$%s', $classLike->qualifiedName(), $property->name)];
                }
            }
            foreach ($classLike->methods as $method) {
                if (
                    $method->visibility === 'public' &&
                    (
                        !$method->source->inProject || $method->owner === $classLike
                    )
                ) {
                    $entries[] = [$type, sprintf('%s::%s()', $classLike->qualifiedName(), $method->name)];
                }
            }
        };

        $addNamespace = function (ProjectNamespace $ns) use (&$addNamespace, $addClassLike) {
            array_walk($ns->children, fn ($ns) => $addNamespace($ns));
            array_walk($ns->interfaces, fn ($classLike) => $addClassLike($classLike));
            array_walk($ns->traits, fn ($classLike) => $addClassLike($classLike));
            array_walk($ns->classes, fn ($classLike) => $addClassLike($classLike));
        };

        array_walk($namespaces, $addNamespace);

        $this->renderTemplate('searchlist.twig', 'searchlist.js', ['entries' => json_encode(array_values($entries))]);
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
