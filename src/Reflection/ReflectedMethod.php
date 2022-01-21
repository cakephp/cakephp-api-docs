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

class ReflectedMethod extends ReflectedFunction
{
    use ClassElementTrait;

    public bool $abstract = false;

    public ?self $overrides = null;

    /**
     * @return bool
     */
    public function implementing(): bool
    {
        if ($this->declared !== $this->owner && $this->declared instanceof ReflectedInterface) {
            return true;
        }

        return false;
    }
}
