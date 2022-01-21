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
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class LoadedProperty extends LoadedNode
{
    public ?TypeNode $type = null;

    public string $visibility = 'public';

    public bool $static = false;

    public ?string $default = null;

    public bool $annotated = false;

    public ?string $declared = null;

    public ?string $defined = null;

    /**
     * @param \PhpParser\Node\Stmt\Property $property Property node
     * @param \PhpParser\Node\Stmt\PropertyProperty $node Property node
     * @param \Cake\ApiDocs\Reflection\Source $source Function source
     * @param \Cake\ApiDocs\Reflection\Context $context Function context
     */
    public function __construct(Property $property, PropertyProperty $node, Source $source, Context $context)
    {
        parent::__construct($node->name->name, $source, $context, $property->getDocComment()?->getText());

        /** @var array<\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode> $vars */
        $vars = $this->doc->node->getVarTagValues();
        if ($vars) {
            $this->type = $vars[0]->type;
        } else {
            $this->type = $property->type ? DocUtil::parseType(PrintUtil::node($property->type)) : null;
        }

        $this->default = $node->default ? PrintUtil::expr($node->default) : null;
    }
}
