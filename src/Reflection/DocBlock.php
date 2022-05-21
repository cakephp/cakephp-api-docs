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
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
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
        foreach ($tags as $tag) {
            if ($tag->value instanceof InvalidTagValueNode) {
                continue;
            }
            $this->tags['param'][$tag->value->parameterName] = $tag->value;
        }

        $tags = $node->getTagsByName('@return');
        if ($tags) {
            $this->tags['return'] = current($tags)->value;
        }

        foreach (['@property', '@property-read', '@property-write'] as $tagName) {
            $tags = $node->getTagsByName($tagName);
            foreach ($tags as $tag) {
                if ($tag->value instanceof InvalidTagValueNode) {
                    continue;
                }
                $this->tags[$tagName][$tag->value->propertyName] = $tag->value;
            }
        }

        $tags = $node->getTagsByName('@method');
        foreach ($tags as $tag) {
            if ($tag->value instanceof InvalidTagValueNode) {
                continue;
            }
            $this->tags['@method'][$tag->value->methodName] = $tag->value;
        }

        $tags = $node->getTagsByName('@throws');
        array_walk($tags, fn($tag) => $this->tags['throws'][] = $tag->value);

        $tags = $node->getTagsByName('@see');
        array_walk($tags, fn($tag) => $this->tags['see'][] = $tag->value);

        $tags = $node->getTagsByName('@link');
        array_walk($tags, fn($tag) => $this->tags['link'][] = $tag->value);

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
