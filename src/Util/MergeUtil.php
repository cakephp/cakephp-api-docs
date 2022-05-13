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

namespace Cake\ApiDocs\Util;

use Cake\ApiDocs\Reflection\DocBlock;
use Cake\ApiDocs\Reflection\ReflectedClassLike;

class MergeUtil
{
    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $target Target class-like
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $source Source class-like
     * @return void
     */
    public static function mergeClassLike(ReflectedClassLike $target, ReflectedClassLike $source): void
    {
        static::mergeConstants($target, $source);
        static::mergeProperties($target, $source);
        static::mergeMethods($target, $source);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $target Target class-like
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $source Source class-like
     * @return void
     */
    protected static function mergeConstants(ReflectedClassLike $target, ReflectedClassLike $source): void
    {
        foreach ($source->constants as $name => $sourceConstant) {
            $targetConstant = $target->constants[$name] ?? null;
            if (!$targetConstant) {
                $target->constants[$name] = clone $sourceConstant;
                continue;
            }

            static::mergeDoc($targetConstant->doc, $sourceConstant->doc);
            if ($targetConstant->type === null) {
                $targetConstant->type = $sourceConstant->type;
            }
        }
        ksort($target->constants);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $target Target class-like
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $source Source class-like
     * @return void
     */
    protected static function mergeProperties(ReflectedClassLike $target, ReflectedClassLike $source): void
    {
        foreach ($source->properties as $name => $sourceProperty) {
            $targetProperty = $target->properties[$name] ?? null;
            if (!$targetProperty) {
                $target->properties[$name] = clone $sourceProperty;
                continue;
            }

            static::mergeDoc($targetProperty->doc, $sourceProperty->doc);
            if (
                $targetProperty->type === null ||
                (
                    !isset($targetProperty->doc->tags['var']) &&
                    $targetProperty->nativeType == $sourceProperty->nativeType
                )
            ) {
                $targetProperty->type = $sourceProperty->type;
            }
        }
        ksort($target->properties);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $target Target class-like
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $source Source class-like
     * @return void
     */
    protected static function mergeMethods(ReflectedClassLike $target, ReflectedClassLike $source): void
    {
        foreach ($source->methods as $name => $sourceMethod) {
            $targetMethod = $target->methods[$name] ?? null;
            if (!$targetMethod) {
                $target->methods[$name] = clone $sourceMethod;
                continue;
            }

            if (count($targetMethod->params) !== count($sourceMethod->params)) {
                // Ignore methods that have different number of parameters such as overriden constructors
                continue;
            }

            static::mergeDoc($targetMethod->doc, $sourceMethod->doc);
            if (
                $targetMethod->returnType === null ||
                (
                    !isset($targetMethod->doc->tags['return']) &&
                    $targetMethod->nativeReturnType == $sourceMethod->nativeReturnType
                )
            ) {
                $targetMethod->returnType = $sourceMethod->returnType;
            }

            foreach ($targetMethod->params as $param) {
                if (
                    isset($sourceMethod->params[$param->name]) &&
                    (
                        $param->type === null ||
                        (
                            !isset($targetMethod->doc->tags['param'][$param->name]) &&
                            $param->nativeType == $sourceMethod->params[$param->name]->nativeType
                        )
                    )
                ) {
                    $param->type = $sourceMethod->params[$param->name]->type;
                }
            }
        }
        ksort($target->methods);
    }

    /**
     * @param \Cake\ApiDocs\Reflection\DocBlock $target Target doc
     * @param \Cake\ApiDocs\Reflection\DocBlock $source Source doc
     * @return bool
     */
    protected static function mergeDoc(DocBlock $target, DocBlock $source): bool
    {
        $inherited = false;
        if (preg_match('/(^$)|(^@inheritDoc$)|(^{@inheritDoc}$)/i', $target->summary)) {
            $target->summary = $source->summary;
            $inherited = true;
        }

        if (preg_match('/(^$)|(^@inheritDoc$)|(^{@inheritDoc}$)/i', $target->description)) {
            $target->description = $source->description;
            $inherited = true;
        } elseif (preg_match('/^(@inheritDoc|{@inheritDoc})\n\n([\s\S]+)/i', $target->description, $matches)) {
            $target->description = $source->description . "\n\n---\n\n" . $matches[2];
            $inherited = true;
        }

        return $inherited;
    }
}
