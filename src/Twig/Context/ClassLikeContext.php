<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\FqsenTree;
use App\InheritanceHelper;
use App\Loader;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Property;

abstract class ClassLikeContext extends ElementContext
{
    /**
     * @var string
     */
    public const CLASS_TYPE = 'class';

    /**
     * @var \App\Loader
     */
    protected $loader;

    /**
     * @var \App\FqsenTree
     */
    protected $inheritanceTree;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The class-like fqsen
     * @param \phpDocumentor\Reflection\Element $element The class-like element
     */
    public function __construct(Loader $loader, string $fqsen, Element $element)
    {
        parent::__construct($fqsen, $element);
        $this->loader = $loader;
    }

    /**
     * Returns the class type.
     *
     * @return string
     */
    public function getClassType(): string
    {
        return static::CLASS_TYPE;
    }

    /**
     * @return \App\FqsenTree
     */
    public function getInheritanceTree(): FqsenTree
    {
        if ($this->element) {
            $node = InheritanceHelper::getFqsenTree($this->loader, $this->element);
            while ($node->getParent()) {
                $node = $node->getParent();
            }

            return $node;
        }

        return new FqsenTree($this->objectType, $this->fqsen, null);
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        $this->getProperties();
        $methods = InheritanceHelper::getMethods($this->loader, $this->element);
        ksort($methods);

        return array_map(function ($method) {
            if ($method instanceof Method) {
                return new MethodContext($this->loader, (string)$method->getFqsen(), $method);
            }

            $fqsen = $this->getFqsen() . '::' . $method->getMethodName() . '()';

            return new DocMethodContext($this->loader, $fqsen, $method);
        }, $methods);
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        $properties = InheritanceHelper::getProperties($this->loader, $this->element);
        ksort($properties);

        return array_map(function ($property) {
            if ($property instanceof Property) {
                return new PropertyContext($this->loader, (string)$property->getFqsen(), $property);
            }

            $fqsen = $this->getFqsen() . '::$' . $property->getVariableName();

            return new DocPropertyContext($this->loader, $fqsen, $property);
        }, $properties);
    }

    /**
     * @return array
     */
    public function getConstants(): array
    {
        $constants = InheritanceHelper::getConstants($this->loader, $this->element);
        ksort($constants);

        return array_map(function ($constant) {
            return new ConstantContext($this->loader, (string)$constant->getFqsen(), $constant);
        }, $constants);
    }
}
