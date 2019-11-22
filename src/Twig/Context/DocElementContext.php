<?php
declare(strict_types=1);

namespace App\Twig\Context;

use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;

class DocElementContext
{
    /**
     * @var string
     */
    protected $fqsen;

    /**
     * @var \phpDocumentor\Reflection\DocBlock\Tags\BaseTag
     */
    protected $tag;

    /**
     * @param string $fqsen The element fqsen
     * @param \phpDocumentor\Reflection\DocBlock\Tags\BaseTag $tag The docbloc tag instance
     */
    protected function __construct(string $fqsen, BaseTag $tag)
    {
        $this->fqsen = $fqsen;
        $this->tag = $tag;
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
    public function getVisibility(): string
    {
        return 'public';
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        return (string)$this->tag->getDescription();
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function getAnnotations(): array
    {
        return ['annotated' => []];
    }
}
