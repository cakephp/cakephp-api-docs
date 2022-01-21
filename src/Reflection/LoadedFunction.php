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

namespace Cake\ApiDocs\Reflection;

use Cake\ApiDocs\DocUtil;
use Cake\ApiDocs\PrintUtil;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class LoadedFunction extends LoadedNode
{
    /**
     * @var array<string, \Cake\ApiDocs\Reflection\Param>
     */
    public array $parameters = [];

    public ?TypeNode $returnType = null;

    public ?string $visibility = null;

    public bool $abstract = false;

    public bool $static = false;

    public bool $annotated = false;

    public ?string $declared = null;

    public ?string $defined = null;

    /**
     * @param \PhpParser\Node\Stmt\Function_ $node Function node
     * @param \Cake\ApiDocs\Reflection\Source $source Node source
     * @param \Cake\ApiDocs\Reflection\Context $context Node context
     */
    public function __construct(ClassMethod|Function_ $node, Source $source, Context $context)
    {
        parent::__construct($node->name->name, $source, $context, $node->getDocComment()?->getText());

        /** @var \PhpParser\Node\Param $param */
        foreach ($node->getParams() as $param) {
            $name = $param->var->name;

            $tag = $this->getParamTag($name);
            if ($tag) {
                $this->parameters[$name] = new Param(
                    $name,
                    $tag->type,
                    $param->variadic,
                    $param->byRef,
                    $param->default ? PrintUtil::expr($param->default) : null,
                    $tag->description
                );
                continue;
            }

            $this->parameters[$name] = new Param(
                $name,
                $param->type ? DocUtil::parseType(PrintUtil::node($param->type)) : null,
                $param->variadic,
                $param->byRef,
                $param->default ? PrintUtil::expr($param->default) : null
            );
        }

        $tag = $this->getReturnTag();
        if ($tag) {
            $this->returnType = $tag->type;
        } else {
            $this->returnType = $node->returnType ? DocUtil::parseType(PrintUtil::node($node->returnType)) : null;
        }

        if ($node instanceof ClassMethod) {
            $this->visibility = $node->isPublic() ? 'public' : ($node->isProtected() ? 'protected' : 'private');
            $this->abstract = $node->isAbstract();
            $this->static = $node->isStatic();
        }
    }

    /**
     * @param string $variable Variable name
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|null
     */
    protected function getParamTag(string $variable): ?ParamTagValueNode
    {
        $variable = '$' . $variable;
        foreach ($this->doc->node->getParamTagValues() as $tag) {
            if ($tag->parameterName === $variable) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode|null
     */
    protected function getReturnTag(): ?ReturnTagValueNode
    {
        $tags = $this->doc->node->getReturnTagValues();

        return $tags[0] ?? null;
    }
}
