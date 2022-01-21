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

namespace Cake\ApiDocs\Reflection;

abstract class ReflectedNode
{
    public string $name;

    /**
     * @param string $qualifiedName Qualified node name
     * @param \Cake\ApiDocs\Reflection\DocBlock $doc Reflected docblock
     * @param \Cake\ApiDocs\Reflection\Context $context Context info
     * @param \Cake\ApiDocs\Reflection\Source $source Source info
     */
    public function __construct(
        public string $qualifiedName,
        public DocBlock $doc,
        public Context $context,
        public Source $source
    ) {
        preg_match('/[^:\\\\]+$/', $qualifiedName, $matches);
        $this->name = $matches[0];
    }
}
