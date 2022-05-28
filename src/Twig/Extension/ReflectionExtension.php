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

namespace Cake\ApiDocs\Twig\Extension;

use Cake\ApiDocs\Project;
use Cake\ApiDocs\Reflection\ReflectedNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * ReflectionExtension
 */
class ReflectionExtension extends AbstractExtension
{
    protected Project $project;

    /**
     * @param \Cake\ApiDocs\Project $project Project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('classlike_to_url', function (string $name, string $type) {
                return sprintf('%s-%s.html', $type, preg_replace('[\\\\]', '.', $name));
            }),
            new TwigFilter('namespace_to_url', function (?string $name) {
                return sprintf('namespace-%s.html', preg_replace('[\\\\]', '.', $name ?: 'Global'));
            }),
            new TwigFilter('type', function (?TypeNode $type) {
                // Remove parenthesis added by phpstan around union and intersection types
                // Remove space around union and intersection delimiter
                // Remove leading \ in front of types
                return preg_replace(
                    [
                        '/ ([|&]) /',
                        '/<\(/',
                        '/\)>/',
                        '/\), /',
                        '/, \(/',
                        '/^\((.*)\)$/',
                        '/(?:^\\\\)|(?:[^_a-zA-Z])(\\\\)/',
                    ],
                    ['${1}', '<', '>', ', ', ', ', '${1}', ''],
                    (string)$type
                );
            }),
            new TwigFilter('node_to_repo_url', function (ReflectedNode $node) {
                preg_match('/^(?:origin\/)?(.*)$/', $this->project->getConfig('tag'), $matches);

                return sprintf(
                    '%s/blob/%s/%s#L%d',
                    $this->project->getConfig('repo'),
                    $matches[1],
                    $node->source->path,
                    $node->source->startLine
                );
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTests()
    {
        return [
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('inNamespace', function (?string $name, ?string $namespace) {
                if ($name === null && $namespace !== null) {
                    return false;
                }

                if ($name === null && $namespace == null) {
                    return true;
                }

                return str_starts_with($namespace ?? '', $name);
            }),
        ];
    }
}
