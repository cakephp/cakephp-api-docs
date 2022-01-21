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

use Cake\ApiDocs\PrintUtil;
use PhpParser\Node\Const_;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class LoadedConstant extends LoadedNode
{
    public string $value;

    public ?TypeNode $type = null;

    public ?string $visibility = null;

    public ?string $declared = null;

    public ?string $defined = null;

    /**
     * @param \PhpParser\Node\Const_ $node Constant node
     * @param \Cake\ApiDocs\Reflection\Source $source Node source
     * @param \Cake\ApiDocs\Reflection\Context $context Node context
     */
    public function __construct(Const_ $node, Source $source, Context $context)
    {
        parent::__construct($node->name->name, $source, $context, $node->getDocComment()?->getText());

        $this->value = PrintUtil::expr($node->value);

        /** @var array<\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode> $vars */
        $vars = $this->doc->node->getVarTagValues();
        if ($vars) {
            $this->type = $vars[0]->type;
        }
    }

    /**
     * @param \Cake\ApiDocs\Reflection\LoadedConstant $other Child node
     * @return void
     */
    public function merge(LoadedConstant $other): void
    {
    }
}
