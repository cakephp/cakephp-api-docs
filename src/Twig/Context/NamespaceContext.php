<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use phpDocumentor\Reflection\Php\Namespace_;

class NamespaceContext
{
    /**
     * @var string
     */
    private $fqsen;

    /**
     * @var string[]
     */
    private $children;

    /**
     * @var \App\Twig\Context\ClasContext[]
     */
    private $classes = [];

    /**
     * @var \App\Twig\Context\InterfaceContext[]
     */
    private $interfaces = [];

    /**
     * @var \App\Twig\Context\TraitContext[]
     */
    private $traits = [];

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The namespace fqsen
     * @param \phpDocumentor\Reflection\Php\Namespace_|null $element The namespace element
     * @param string[] $children The namespace children
     */
    public function __construct(Loader $loader, string $fqsen, ?Namespace_ $element, array $children)
    {
        $this->fqsen = $fqsen;
        $this->children = $children;

        if ($element) {
            foreach ($element->getClasses() as $fqsen) {
                $fqsen = (string)$fqsen;
                [$class, $inProject] = $loader->getClass($fqsen);
                $this->classes[$fqsen] = new ClassContext($loader, $fqsen, $class);
            }
            foreach ($element->getInterfaces() as $fqsen) {
                $fqsen = (string)$fqsen;
                [$interface, $inProject] = $loader->getClass($fqsen);
                $this->interfaces[$fqsen] = new InterfaceContext($loader, $fqsen, $interface);
            }
            foreach ($element->getTraits() as $fqsen) {
                $fqsen = (string)$fqsen;
                [$trait, $inProject] = $loader->getClass($fqsen);
                $this->traits[$fqsen] = new TraitContext($loader, $fqsen, $trait);
            }
        }
    }

    /**
     * @return string
     */
    public function getFqsen(): string
    {
        return $this->fqsen;
    }

    /**
     * @return string[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return \App\Twig\Context\ClassContext[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @return \App\Twig\Context\InterfaceContext[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return \App\Twig\Context\TraitContext[]
     */
    public function getTraits(): array
    {
        return $this->traits;
    }
}
