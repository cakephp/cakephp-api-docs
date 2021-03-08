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

use phpDocumentor\Reflection\Php\File;

class LoadedFile
{
    /**
     * @var \phpDocumentor\Reflection\Php\File
     */
    public $file;

    /**
     * @var bool
     */
    public $fromVendor;

    /**
     * @param \phpDocumentor\Reflection\Php\File $file Reflection file
     * @param bool $fromVendor Whether the file was loaded from vendor/ directory
     */
    public function __construct(File $file, bool $fromVendor)
    {
        $this->file = $file;
        $this->fromVendor = $fromVendor;
    }
}
