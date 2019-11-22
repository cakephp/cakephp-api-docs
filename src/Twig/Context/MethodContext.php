<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use phpDocumentor\Reflection\Php\Method;

class MethodContext extends ElementContext
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
     * @var \phpDocumentor\Reflection\Php\Method
     */
    protected $element;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The method fqsen
     * @param \phpDocumentor\Reflection\Php\Method $method The method element
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
        if ($this->element->isFinal()) {
            $modifiers[] = 'final';
        }
        if ($this->element->isAbstract()) {
            $modifiers[] = 'abstract';
        }
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
     * @return array
     */
    public function getParameters(): array
    {
        $parameters = array_map(function ($argument) {
            $definition = '';
            if ($argument->isVariadic()) {
                $definition .= '...';
            }
            if ($argument->isByReference()) {
                $definition .= '&';
            }
            $definition .= '$' . $argument->getName();
            if ($argument->getDefault() !== null) {
                $definition .= ' = ' . $argument->getDefault();
            }

            $parameter = [
                'definition' => $definition,
                'name' => '$' . $argument->getName(),
                'type' => (string)($argument->getType() ?? ''),
                'default' => $argument->getDefault(),
                'description' => '',
            ];

            $varTags = $this->getAnnotations()['param'] ?? [];
            foreach ($varTags as $tag) {
                if ($tag['name'] === $parameter['name']) {
                    $parameter['description'] = $tag['description'];
                    break;
                }
            }

            return $parameter;
        }, $this->element->getArguments());

        return $parameters;
    }
}
