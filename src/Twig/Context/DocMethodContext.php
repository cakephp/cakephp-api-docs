<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use phpDocumentor\Reflection\DocBlock\Tags\Method;

class DocMethodContext extends DocElementContext
{
    /**
     * @var \App\Loader
     */
    protected $loader;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The method fqsen
     * @param \phpDocumentor\Reflection\DocBlock\Tags\Method $method The method element
     */
    public function __construct(Loader $loader, string $fqsen, Method $method)
    {
        parent::__construct($fqsen, $method);
        $this->loader = $loader;
    }

    /**
     * @return string[]
     */
    public function getModifiers(): array
    {
        $modifiers = [];
        if ($this->tag->isStatic()) {
            $modifiers[] = 'static';
        }

        return $modifiers;
    }

    /**
     * @return array
     */
    public function getAnnotations(): array
    {
        $annotations = parent::getAnnotations();
        $annotations['return'] = [
            'type' => (string)$this->tag->getReturnType(),
            'description' => '',
        ];

        return $annotations;
    }
}
