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

namespace Cake\ApiDocs\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * ReflectionExtension
 */
class ReflectionExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('url', function (string $name, string $type) {
                return sprintf('%s-%s.html', $type, preg_replace('[\\\\]', '.', $name));
            }),
            new TwigFilter('namespace_url', function (?string $name) {
                return sprintf('namespace-%s.html', preg_replace('[\\\\]', '.', $name ?: 'Global'));
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTests()
    {
        return [
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
        ];
    }
}
