<?php
declare(strict_types=1);

namespace App\Twig\Context;

use phpDocumentor\Reflection\DocBlock\Tags\Reference\Fqsen as DocFqsen;
use phpDocumentor\Reflection\Element;

class ElementContext
{
    /**
     * @var string
     */
    protected $fqsen;

    /**
     * @var \phpDocumentor\Reflection\Php\Element
     */
    protected $element;

    /**
     * @var array
     */
    private $annotations = null;

    /**
     * @param string $fqsen The element fqsen
     * @param \phpDocumentor\Reflection\Element $element The element instance
     */
    protected function __construct(string $fqsen, Element $element)
    {
        $this->fqsen = $fqsen;
        $this->element = $element;
    }

    /**
     * @return string
     */
    public function getFqsen(): string
    {
        return $this->fqsen;
    }

    /**
     * @return string
     */
    public function getNamespaceFqsen(): string
    {
        return substr($this->fqsen, 0, strrpos($this->fqsen, '\\'));
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        if ($this->element === null || $this->element->getDocBlock() === null) {
            return '';
        }

        return (string)$this->element->getDocBlock()->getSummary();
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        if ($this->element === null || $this->element->getDocBlock() === null) {
            return '';
        }

        return (string)$this->element->getDocBlock()->getDescription();
    }

    /**
     * @return array
     */
    public function getAnnotations(): array
    {
        if ($this->annotations !== null) {
            return $this->annotations;
        }

        $this->annotations = [];
        foreach ($this->getTags('deprecated') as $tag) {
            $this->annotations['deprecated'] = [
                'version' => $tag->getVersion(),
                'description' => $tag->getDescription(),
            ];
        }

        foreach ($this->getTags('covers') as $tag) {
            if ($tag->getReference() !== null) {
                $this->annotations['covers'][] = [
                    'fqsen' => (string)$tag->getReference(),
                    'description' => $tag->getDescription(),
                ];
            }
        }

        foreach ($this->getTags('see') as $tag) {
            $annotation = [
                'description' => $tag->getDescription(),
            ];

            $reference = $tag->getReference();
            if ($reference instanceof DocFqsen) {
                $annotation['fqsen'] = (string)$reference;
            } else {
                $annotation['url'] = (string)$reference;
            }

            $this->annotations['see'][] = $annotation;
        }

        foreach ($this->getTags('link') as $tag) {
            $this->annotations['link'][] = [
                'link' => $tag->getLink(),
                'description' => $tag->getDescription(),
            ];
        }

        foreach ($this->getTags('var') as $tag) {
            $this->annotations['var'] = [
                'name' => $tag->getVariableName(),
                'type' => (string)$tag->getType(),
                'description' => $tag->getDescription(),
            ];
        }

        foreach ($this->getTags('param') as $tag) {
            $this->annotations['param'][] = [
                'name' => $tag->isVariadic() ? '...' : '' . '$' . $tag->getVariableName(),
                'type' => (string)$tag->getType(),
                'description' => $tag->getDescription(),
            ];
        }

        foreach ($this->getTags('return') as $tag) {
            $this->annotations['return'] = [
                'type' => (string)$tag->getType(),
                'description' => $tag->getDescription(),
            ];
        }

        foreach ($this->getTags('throws') as $tag) {
            $this->annotations['throws'][] = [
                'type' => (string)$tag->getType(),
                'description' => $tag->getDescription(),
            ];
        }

        return $this->annotations;
    }

    /**
     * @param string $name The docblock tag type
     * @return array
     */
    private function getTags(string $name): array
    {
        if ($this->element === null || $this->element->getDocBlock() === null) {
            return [];
        }

        return $this->element->getDocBlock()->getTagsByName($name);
    }
}
