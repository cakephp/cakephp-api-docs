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

namespace Cake\ApiDocs\Util;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\PrettyPrinter\Standard;

class PrintUtil
{
    /**
     * @param \PhpParser\Node\Expr $expr Expression
     * @return string
     */
    public static function expr(Expr $expr): string
    {
        return static::printer()->prettyPrintExpr($expr);
    }

    /**
     * @param \PhpParser\Node $node Node
     * @return string
     */
    public static function node(Node $node): string
    {
        return static::printer()->prettyPrint([$node]);
    }

    /**
     * @return \PhpParser\PrettyPrinter\Standard
     */
    protected static function printer(): Standard
    {
        static $printer;
        if (!isset($printer)) {
            $printer = new Standard();
        }

        return $printer;
    }
}
