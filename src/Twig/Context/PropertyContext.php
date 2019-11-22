<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use phpDocumentor\Reflection\Php\Property;

class PropertyContext extends ElementContext
{
    /**
     * @var \App\Loader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $fqsen;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \phpDocumentor\Reflection\Php\Property
     */
    protected $element;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The property fqsen
     * @param \phpDocumentor\Reflection\Php\Property $property The property element
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
        $modifiers = [];
        if ($this->element->isStatic()) {
            $modifiers[] = 'static';
        }

        return $modifiers;
    }

    /**
     * @return string
     */
    public function getVisibility(): string
    {
        return (string)($this->element->getVisibility() ?? 'public');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if (!isset($this->getAnnotations()['var']['type'])) {
            return '';
        }

        return $this->getAnnotations()['var']['type'];
    }
}
