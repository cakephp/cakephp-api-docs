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

use Cake\ApiDocs\Reflection\Context;
use Cake\ApiDocs\Reflection\DocBlock;
use Cake\ApiDocs\Reflection\ReflectedClass;
use Cake\ApiDocs\Reflection\ReflectedClassLike;
use Cake\ApiDocs\Reflection\ReflectedConstant;
use Cake\ApiDocs\Reflection\ReflectedDefine;
use Cake\ApiDocs\Reflection\ReflectedFunction;
use Cake\ApiDocs\Reflection\ReflectedInterface;
use Cake\ApiDocs\Reflection\ReflectedMethod;
use Cake\ApiDocs\Reflection\ReflectedParam;
use Cake\ApiDocs\Reflection\ReflectedProperty;
use Cake\ApiDocs\Reflection\ReflectedTrait;
use Cake\ApiDocs\Reflection\Source;
use Cake\ApiDocs\Util\DocUtil;
use Cake\ApiDocs\Util\PrintUtil;
use PhpParser\Node\Const_;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;

class Factory
{
    /**
     * @param \PhpParser\Node\Const_ $node Const node
     * @param \Cake\ApiDocs\Reflection\Context $context Reflection context
     * @param \Cake\ApiDocs\Reflection\Source $source Reflection source
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    public function createDefine(Const_ $node, Context $context, Source $source): ReflectedDefine
    {
        $doc = new DocBlock($node->getDocComment()?->getText());

        $const = new ReflectedDefine($node->name->name, $doc, $context, $source);
        $const->value = PrintUtil::expr($node->value);

        if (isset($doc->tags['var'])) {
            $const->type = $doc->tags['var']->type;
        }

        return $const;
    }

    /**
     * @param \PhpParser\Node\Stmt\Function_ $node Function node
     * @param \Cake\ApiDocs\Reflection\Context $context Reflection context
     * @param \Cake\ApiDocs\Reflection\Source $source Reflection source
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    public function createFunction(Function_ $node, Context $context, Source $source): ReflectedFunction
    {
        $doc = new DocBlock($node->getDocComment()?->getText());

        $func = new ReflectedFunction($node->name->name, $doc, $context, $source);
        $this->reflectFuncLike($func, $node, $doc);

        return $func;
    }

    /**
     * @param \PhpParser\Node\Stmt\Interface_ $node Interface node
     * @param \Cake\ApiDocs\Reflection\Context $context Reflection context
     * @param \Cake\ApiDocs\Reflection\Source $source Reflection source
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    public function createInterface(Interface_ $node, Context $context, Source $source): ReflectedInterface
    {
        $doc = new DocBlock($node->getDocComment()?->getText());

        $interface = new ReflectedInterface($node->name->name, $doc, $context, $source);
        $this->reflectClassLike($interface, $node);

        $interface->extends = array_map(fn($name) => (string)$name, $node->extends);

        return $interface;
    }

    /**
     * @param \PhpParser\Node\Stmt\Class_ $node Class node
     * @param \Cake\ApiDocs\Reflection\Context $context Reflection context
     * @param \Cake\ApiDocs\Reflection\Source $source Reflection source
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    public function createClass(Class_ $node, Context $context, Source $source): ReflectedClass
    {
        $doc = new DocBlock($node->getDocComment()?->getText());

        $class = new ReflectedClass($node->name->name, $doc, $context, $source);
        $this->reflectClassLike($class, $node);

        $class->abstract = $node->isAbstract();
        $class->final = $node->isFinal();
        $class->extends = (string)$node->extends ?: null;
        $class->implements = array_map(fn($name) => (string)$name, $node->implements);

        return $class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Trait_ $node Trait node
     * @param \Cake\ApiDocs\Reflection\Context $context Reflection context
     * @param \Cake\ApiDocs\Reflection\Source $source Reflection source
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    public function createTrait(Trait_ $node, Context $context, Source $source): ReflectedTrait
    {
        $doc = new DocBlock($node->getDocComment()?->getText());
        $trait = new ReflectedTrait($node->name->name, $doc, $context, $source);
        $this->reflectClassLike($trait, $node);

        return $trait;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $classLike Reflected classLike
     * @param \PhpParser\Node\Stmt\ClassConst $classNode Constant node
     * @param \PhpParser\Node\Const_ $constNode Constant node
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    protected function createConstant(
        ReflectedClassLike $classLike,
        ClassConst $classNode,
        Const_ $constNode
    ): ReflectedConstant {
        $doc = new DocBlock($classNode->getDocComment()?->getText());
        $source = new Source(
            $classLike->source->path,
            $classLike->source->inProject,
            $classNode->getStartLine(),
            $classNode->getEndLine()
        );

        $const = new ReflectedConstant(
            $constNode->name->name,
            $doc,
            $classLike->context,
            $source
        );
        $const->owner = $classLike;

        $const->value = PrintUtil::expr($constNode->value);
        if (isset($doc->tags['var'])) {
            $const->type = $doc->tags['var']->type;
        }

        $const->visibility = $classNode->isPublic() ? 'public' : ($classNode->isProtected() ? 'protected' : 'private');

        return $const;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $classLike Reflected classLike
     * @param \PhpParser\Node\Stmt\Property $classNode Property node
     * @param \PhpParser\Node\Stmt\PropertyProperty $propNode Property node
     * @return \Cake\ApiDocs\Reflection\ReflectedDefine
     */
    protected function createProperty(
        ReflectedClassLike $classLike,
        Property $classNode,
        PropertyProperty $propNode
    ): ReflectedProperty {
        $doc = new DocBlock($classNode->getDocComment()?->getText());
        $source = new Source(
            $classLike->source->path,
            $classLike->source->inProject,
            $classNode->getStartLine(),
            $classNode->getEndLine()
        );

        $prop = new ReflectedProperty(
            $propNode->name->name,
            $doc,
            $classLike->context,
            $source
        );
        $prop->owner = $classLike;

        $prop->nativeType = $classNode->type ? DocUtil::parseType(PrintUtil::node($classNode->type)) : null;
        $prop->type = $doc->tags['var']?->type ?? $prop->nativeType;
        $prop->default = $propNode->default ? PrintUtil::expr($propNode->default) : null;

        $prop->visibility = $classNode->isPublic() ? 'public' : ($classNode->isProtected() ? 'protected' : 'private');
        $prop->static = $classNode->isStatic();

        return $prop;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $classLike Reflected classLike
     * @param \PhpParser\Node\Stmt\ClassMethod $node Method node
     * @return \Cake\ApiDocs\Reflection\ReflectedMethod
     */
    protected function createMethod(ReflectedClassLike $classLike, ClassMethod $node): ReflectedMethod
    {
        $doc = new DocBlock($node->getDocComment()?->getText());
        $source = new Source(
            $classLike->source->path,
            $classLike->source->inProject,
            $node->getStartLine(),
            $node->getEndLine()
        );

        $func = new ReflectedMethod(
            $node->name->name,
            $doc,
            $classLike->context,
            $source
        );
        $func->owner = $classLike;

        $this->reflectFuncLike($func, $node, $doc);
        $func->visibility = $node->isPublic() ? 'public' : ($node->isProtected() ? 'protected' : 'private');
        $func->abstract = $node->isAbstract();
        $func->static = $node->isStatic();

        return $func;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedFunction $func Reflected function
     * @param \PhpParser\Node\Param $node Param node
     * @return \Cake\ApiDocs\Reflection\ReflectedParam
     */
    protected function createParam(ReflectedFunction $func, Param $node): ReflectedParam
    {
        $param = new ReflectedParam($node->var->name);

        $tag = $this->getParamTag($param->name, $func->doc);
        $param->nativeType = $node->type ? DocUtil::parseType(PrintUtil::node($node->type)) : null;
        if ($tag) {
            $param->type = $tag->type;
            $param->description = $tag->description;
        } else {
            $param->type = $param->nativeType;
        }

        $param->variadic = $node->variadic;
        $param->byRef = $node->byRef;
        $param->default = $node->default ? PrintUtil::expr($node->default) : null;

        return $param;
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedClassLike $classLike Reflected classLike
     * @param \PhpParser\Node\Stmt\ClassLike $node ClassLike node
     * @return void
     */
    protected function reflectClassLike(ReflectedClassLike $classLike, ClassLike $node): void
    {
        foreach ($node->getConstants() as $classConstNode) {
            foreach ($classConstNode->consts as $constNode) {
                if (!$classConstNode->isPrivate()) {
                    $constant = $this->createConstant($classLike, $classConstNode, $constNode);
                    $classLike->constants[$constant->name] = $constant;
                }
            }
        }
        ksort($classLike->constants);

        foreach ($node->getProperties() as $classPropNode) {
            foreach ($classPropNode->props as $propNode) {
                if (!$classPropNode->isPrivate()) {
                    $property = $this->createProperty($classLike, $classPropNode, $propNode);
                    $classLike->properties[$property->name] = $property;
                }
            }
        }
        ksort($classLike->properties);

        foreach ($node->getmethods() as $methodNode) {
            if (!$methodNode->isPrivate()) {
                $method = $this->createMethod($classLike, $methodNode);
                $classLike->methods[$method->name] = $method;
            }
        }
        ksort($classLike->methods);

        $traits = $node->getTraitUses();
        foreach ($traits as $trait) {
            foreach ($trait->traits as $use) {
                $classLike->uses[] = (string)$use;
            }
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\ReflectedFunction $func Reflected function
     * @param \PhpParser\Node\FunctionLike $node Function node
     * @param \Cake\ApiDocs\Reflection\DocBlock $doc Reflected docblock
     * @return void
     */
    protected function reflectFuncLike(ReflectedFunction $func, FunctionLike $node, DocBlock $doc): void
    {
        $params = [];
        foreach ($node->getParams() as $paramNode) {
            $param = $this->createParam($func, $paramNode);
            $func->params[$param->name] = $param;
        }

        if ($node->getReturnType() !== null) {
            $func->nativeReturnType = DocUtil::parseType(PrintUtil::node($node->getReturnType()));
        }
        $func->returnType = $doc->tags['return']?->type ?? $func->nativeReturnType;
    }

    /**
     * @param string $variable Variable name
     * @param \Cake\ApiDocs\Reflection\DocBlock $doc Reflected docblock
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|null
     */
    protected function getParamTag(string $variable, DocBlock $doc): ?ParamTagValueNode
    {
        $variable = '$' . $variable;
        foreach ($doc->tags['param'] ?? [] as $tag) {
            if ($tag instanceof InvalidTagValueNode) {
                continue;
            }
            if ($tag->parameterName === $variable) {
                return $tag;
            }
        }

        return null;
    }
}
