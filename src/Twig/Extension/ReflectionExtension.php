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

namespace Cake\ApiDocs\Twig\Extension;

use Cake\ApiDocs\Reflection\LoadedClass;
use Cake\ApiDocs\Reflection\LoadedClassLike;
use Cake\ApiDocs\Reflection\LoadedConstant;
use Cake\ApiDocs\Reflection\LoadedFunction;
use Cake\ApiDocs\Reflection\LoadedMethod;
use Cake\ApiDocs\Reflection\LoadedNamespace;
use Cake\ApiDocs\Reflection\LoadedProperty;
use Cake\Core\Configure;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * ReflectionExtension
 */
class ReflectionExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('fqsen', function (string $fqsen) {
                return substr($fqsen, 1);
            }),
            new TwigFilter('fqsen_to_name', function (string $fqsen) {
                return substr($fqsen, strrpos($fqsen, '\\') + 1);
            }),
            new TwigFilter('fqsen_to_url', function (string $fqsen, string $type) {
                return sprintf(
                    '%s-%s.html',
                    $type,
                    preg_replace('[\\\\]', '.', $fqsen === '\\' ? 'Global' : substr($fqsen, 1))
                );
            }),
            new TwigFilter('docblock', function ($loaded) {
                if ($loaded instanceof LoadedClassLike) {
                    return $loaded->element->getDocBlock() ?? new DocBlock();
                }
                if ($loaded instanceof LoadedConstant) {
                    return $loaded->docBlock;
                }
                if ($loaded instanceof LoadedMethod) {
                    return $loaded->docBlock;
                }
                if ($loaded instanceof LoadedProperty) {
                    return $loaded->docBlock;
                }
                if ($loaded instanceof LoadedFunction) {
                    return $loaded->function->getDocBlock() ?? new DocBlock();
                }
            }),
            new TwigFilter('get_tags', function ($docblock, $name) {
                $tags = [];
                foreach ($docblock->getTags() as $tag) {
                    if (!$tag instanceof InvalidTag && $tag->getName() === $name) {
                        $tags[] = $tag;
                    }
                }

                return $tags;
            }),
            new TwigFilter('get_tag', function (DocBlock $docBlock, $name) {
                foreach ($docBlock->getTags() as $tag) {
                    if (!$tag instanceof InvalidTag && $tag->getName() === $name) {
                        return $tag;
                    }
                }

                return null;
            }),
            new TwigFilter('param', function ($docblock, $name) {
                $params = $docblock->getTagsByName('param');
                foreach ($params as $param) {
                    if ($param->getVariableName() === $name) {
                        return $param;
                    }
                }

                return null;
            }),
            new TwigFilter('srcPath', function ($loaded) {
                if ($loaded instanceof LoadedConstant) {
                    return $this->buildGithubLink(
                        $loaded->filePath,
                        $loaded->constant->getLocation()
                            ->getLineNumber()
                    );
                }
                if ($loaded instanceof LoadedMethod) {
                    return $this->buildGithubLink(
                        $loaded->filePath,
                        $loaded->method->getLocation()
                            ->getLineNumber()
                    );
                }
                if ($loaded instanceof LoadedProperty) {
                    return $this->buildGithubLink(
                        $loaded->filePath,
                        $loaded->property->getLocation()
                            ->getLineNumber()
                    );
                }
                if ($loaded instanceof LoadedFunction) {
                    return $this->buildGithubLink(
                        $loaded->filePath,
                        $loaded->function->getLocation()
                            ->getLineNumber()
                    );
                }
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTests()
    {
        return [
            new TwigTest('class', function (LoadedClassLike $loaded) {
                return $loaded instanceof LoadedClass;
            }),
            new TwigTest('in_namespace', function ($loaded, string $namespace) {
                if ($loaded === null) {
                    return false;
                }
                if (strpos($loaded->fqsen, $namespace) === 0) {
                    if ($loaded instanceof LoadedNamespace) {
                        return true;
                    }

                    return $loaded->fqsen !== $namespace;
                }

                return false;
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [];
    }

    /**
     * @param string $filePath Path to the file
     * @param int $lineNr The line number in which the function/property etc. is present
     * @return string
     */
    private function buildGithubLink(string $filePath, int $lineNr)
    {
        $repo = Configure::read('config');
        if ($repo === 'elastic') {
            $repo = 'elastic-search';
        }

        $version = Configure::read('version');
        $basePath = Configure::read('basePath');
        $repoPath = str_replace($basePath, '', $filePath);

        return 'https://github.com/cakephp/' . $repo . '/tree/' . $version . $repoPath . '#L' . $lineNr;
    }
}
