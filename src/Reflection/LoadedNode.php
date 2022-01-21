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

class LoadedNode
{
    public string $name;

    public Source $source;

    public Context $context;

    public DocBlock $doc;

    /**
     * @param string $name Node name
     * @param \Cake\ApiDocs\Reflection\Source $source Function source
     * @param \Cake\ApiDocs\Reflection\Context $context Function context
     * @param string|null $docBlock Full docblock commment
     */
    public function __construct(string $name, Source $source, Context $context, ?string $docBlock = null)
    {
        $this->name = $name;
        $this->source = $source;
        $this->context = $context;
        $this->doc = new DocBlock($docBlock ?? '');
    }

    /**
     * @return string
     */
    public function namespaced(): string
    {
        return $this->context->namespaced($this->name);
    }
}
