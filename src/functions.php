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

use Cake\Core\Configure;

/**
 * @param string $fqsen Fqsen to check if excluded
 * @param bool $isNamespace Whether $fqsen is a namespace
 * @return bool
 */
function isExcluded(string $fqsen, bool $isNamespace): bool
{
    $namespace = substr($fqsen, 0, strrpos($fqsen, '\\'));
    if ($isNamespace) {
        if (in_array($namespace, Configure::read('excludes.namespaces', []), true)) {
            return true;
        }

        return in_array($fqsen, Configure::read('excludes.namespaces', []), true);
    }

    if (in_array($namespace, Configure::read('excludes.namespaces', []), true)) {
        return true;
    }

    return in_array($fqsen, Configure::read('excludes.names', []), true);
}
