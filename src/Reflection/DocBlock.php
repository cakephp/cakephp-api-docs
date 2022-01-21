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

use Cake\ApiDocs\Util\DocUtil;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;

class DocBlock
{
    public string $summary = '';

    public string $description = '';

    public array $tags = [];

    /**
     * @param string|null $block Full docblock comment
     */
    public function __construct(?string $block)
    {
        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode $node */
        $node = DocUtil::parseBlock($block);
        foreach ($node->children as $childNode) {
            if (!$childNode instanceof PhpDocTextNode) {
                break;
            }

            if (!$this->summary) {
                $this->summary = $childNode->text;
            } else {
                if ($this->description) {
                    $this->description .= "\n" . $childNode->text;
                } else {
                    $this->description = $childNode->text;
                }
            }
        }

        $tags = $node->getTagsByName('@var');
        if ($tags) {
            $this->tags['var'] = current($tags)->value;
        }

        $tags = $node->getTagsByName('@param');
        array_map(fn($tag) => $this->tags['param'][$tag->value->parameterName] = $tag->value, $tags);

        $tags = $node->getTagsByName('@return');
        if ($tags) {
            $this->tags['return'] = current($tags)->value;
        }

        $tags = $node->getTagsByName('@property');
        array_map(fn($tag) => $this->tags['property'][$tag->value->propertyName] = $tag->value, $tags);

        $tags = $node->getTagsByName('@property-read');
        array_map(fn($tag) => $this->tags['property'][$tag->value->propertyName] = $tag->value, $tags);

        $tags = $node->getTagsByName('@property-write');
        array_map(fn($tag) => $this->tags['property'][$tag->value->propertyName] = $tag->value, $tags);

        $tags = $node->getTagsByName('@method');
        array_map(fn($tag) => $this->tags['method'][$tag->value->methodName] = $tag->value, $tags);

        $tags = $node->getTagsByName('@throws');
        array_map(fn($tag) => $this->tags['throws'][] = $tag->value, $tags);

        $tags = $node->getTagsByName('@see');
        array_map(fn($tag) => $this->tags['see'][] = $tag->value, $tags);

        $tags = $node->getTagsByName('@link');
        array_map(fn($tag) => $this->tags['link'][] = $tag->value, $tags);

        $tags = $node->getTagsByName('@deprecated');
        if ($tags) {
            $this->tags['deprecated'] = current($tags)->value;
        }

        $tags = $node->getTagsByName('@experimental');
        if ($tags) {
            $this->tags['experimental'] = current($tags)->value;
        }

        $tags = $node->getTagsByName('@internal');
        if ($tags) {
            $this->tags['internal'] = current($tags)->value;
        }
    }
}
