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

namespace Cake\ApiDocs;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

class DocUtil
{
    protected static bool $initialized = false;

    protected static TypeParser $typeParser;

    protected static PhpDocParser $docParser;

    protected static Lexer $docLexer;

    /**
     * @param string|null $block Full docblock comment
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode
     */
    public static function parseBlock(?string $block): PhpDocNode
    {
        static::init();

        if ($block) {
            return static::$docParser->parse(new TokenIterator(static::$docLexer->tokenize($block)));
        }

        return new PhpDocNode([]);
    }

    /**
     * @param string $type Type string
     * @return \PHPStan\PhpDocParser\Ast\Type\TypeNode
     */
    public static function parseType(string $type): TypeNode
    {
        static::init();

        return static::$typeParser->parse(new TokenIterator(static::$docLexer->tokenize($type)));
    }

    /**
     * Initialize parser.
     *
     * @return void
     */
    protected static function init(): void
    {
        if (!static::$initialized) {
            $exprParser = new ConstExprParser();
            static::$typeParser = new TypeParser($exprParser);
            static::$docParser = new PhpDocParser(static::$typeParser, $exprParser);
            static::$docLexer = new Lexer();
        }
    }
}
