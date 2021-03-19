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

use phpDocumentor\Reflection\Php\Trait_;

class LoadedTrait extends LoadedClassLike
{
    /**
     * @var \phpDocumentor\Reflection\Php\Trait_
     */
    public Trait_ $trait;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Php\Trait_ $trait Reflection trait
     * @param \Cake\ApiDocs\Reflection\LoadedFile $loadedFile Loaded file
     */
    public function __construct(string $fqsen, Trait_ $trait, LoadedFile $loadedFile)
    {
        parent::__construct($fqsen, $trait, $loadedFile);
    }
}
