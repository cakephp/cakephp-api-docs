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
use Cake\ApiDocs\Reflection\LoadedInterface;
use Cake\ApiDocs\Reflection\LoadedMethod;
use Cake\ApiDocs\Reflection\LoadedNamespace;
use Cake\ApiDocs\Reflection\LoadedProperty;
use Cake\ApiDocs\Reflection\LoadedTrait;
use phpDocumentor\Reflection\DocBlock;
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
                if ($loaded instanceof LoadedInterface) {
                    return $loaded->interface->getDocBlock() ?? new DocBlock();
                }
                if ($loaded instanceof LoadedClass) {
                    return $loaded->class->getDocBlock() ?? new DocBlock();
                }
                if ($loaded instanceof LoadedTrait) {
                    return $loaded->trait->getDocBlock() ?? new DocBlock();
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
                    if ($tag->getName() === $name) {
                        $tags[] = $tag;
                    }
                }

                return $tags;
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
        return [
        ];
    }
}
