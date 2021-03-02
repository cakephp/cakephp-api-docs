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

use phpDocumentor\Reflection\Php\Interface_;

class LoadedInterface extends LoadedClassLike
{
    public Interface_ $interface;

    /**
     * @param string $fqsen fqsen
     * @param \Cake\ApiDocs\Reflection\LoadedFile $loadedFile Loaded file
     * @param \phpDocumentor\Reflection\Php\Interface_ $interface Interface instance
     */
    public function __construct(string $fqsen, LoadedFile $loadedFile, Interface_ $interface)
    {
        parent::__construct($fqsen, $loadedFile);
        $this->interface = $interface;
    }
}
