<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use phpDocumentor\Reflection\DocBlock\Tags\Property;

class DocPropertyContext extends DocElementContext
{
    /**
     * @var \App\Loader
     */
    protected $loader;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The property fqsen
     * @param \phpDocumentor\Reflection\DocBlock\Tags\Property $property The property element
     */
    public function __construct(Loader $loader, string $fqsen, Property $property)
    {
        parent::__construct($fqsen, $property);
        $this->loader = $loader;
    }

    /**
     * @return string[]
     */
    public function getModifiers(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return (string)$this->tag->getType();
    }
}
