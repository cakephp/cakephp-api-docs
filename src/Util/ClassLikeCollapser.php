<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Util;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Trait_;

/**
 * ClassLikeCollapser
 */
class ClassLikeCollapser
{
    /**
     * @var \Cake\ApiDocs\Util\SourceLoader
     */
    protected $loader;

    /**
     * @param \Cake\ApiDocs\Util\SourceLoader $loader source loader
     */
    public function __construct(SourceLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Collapsed inheritance tree for class like fqsens.
     *
     * @param \phpDocumentor\Reflection\Php\Class_|\phpDocumentor\Reflection\Php\Interface_|\phpDocumentor\Reflection\Php\Trait_ $classlike classlike
     * @return \Cake\ApiDocs\Util\CollapsedClassLike
     * @throws \InvalidArgumentException When cannot find $fqsen
     */
    public function collapse($classlike): CollapsedClassLike
    {
        if (
            !($classlike instanceof Class_) &&
            !($classlike instanceof Interface_) &&
            !($classlike instanceof Trait_)
        ) {
            throw new InvalidArgumentException("{$classlike->getFqsen()} is not a class-like fqsen.");
        }

        $source = $this->loader->find((string)$classlike->getFqsen());
        if (!$source) {
            throw new InvalidArgumentException("Unable to find {$classlike->getFqsen()}.");
        }

        $inheritance = $this->getInheritance($source);

        return new CollapsedClassLike(
            $source,
            $this->getElements($inheritance, 'getConstants'),
            $this->getElements($inheritance, 'getProperties'),
            $this->getElements($inheritance, 'getMethods')
        );
    }

    /**
     * @param array $inheritance collapsed inheritance tree
     * @param string $getter element getter
     * @return array
     */
    protected function getElements(array $inheritance, string $getter): array
    {
        $elements = [];
        foreach ($inheritance as $source) {
            if (method_exists($source->getElement(), $getter)) {
                foreach ($source->getElement()->{$getter}() as $fqsen => $element) {
                    $elements[$element->getName()][] = $this->loader->find((string)$fqsen);
                }
            }
        }
        sort($elements);

        foreach ($elements as &$sources) {
            $docBlock = $this->collapseDocBlock($sources);
            $sources = ['source' => reset($sources), 'docBlock' => $docBlock];
        }

        return $elements;
    }

    /**
     * Collapsed doc blocks for element.
     *
     * @param \Cake\ApiDocs\Util\LoadedFqsen[] $sources sources
     * @return \phpDocumentor\Reflection\DocBlock
     */
    protected function collapseDocBlock(array $sources): ?DocBlock
    {
        $summary = '';
        $description = '';
        $tags = [];
        foreach ($sources as $source) {
            /** @var \phpDocumentor\Reflection\DocBlock $docBlock */
            $docBlock = $source->getElement()->getDocBlock();
            if (!$docBlock) {
                break;
            }

            if ($docBlock->getTagsByName('inheritDoc')) {
                continue;
            }

            if ($docBlock->getSummary() === '{@inheritDoc}') {
                $description .= $docBlock->getDescription()->getBodyTemplate();
                $tags = array_merge($docBlock->getTags());
                continue;
            }

            $summary = $docBlock->getSummary();
            $description = $docBlock->getDescription()->getBodyTemplate() . $description;
            $tags = array_merge($tags, $docBlock->getTags());
            break;
        }

        return new DocBlock($summary, new Description($description), $tags);
    }

    /**
     * Builds ordered dictionary of collapsed inheritance tree.
     *
     * @param \Cake\ApiDocs\Util\LoadedFqsen $source loaded fqsen
     * @return \Cake\ApiDocs\Util\LoadedFqsen[]
     */
    protected function getInheritance(LoadedFqsen $source): array
    {
        $walker = function (Element $element, string $getter): array {
            if (!method_exists($element, $getter)) {
                return [];
            }

            $inheritance = [];
            $elements = (array)$element->{$getter}();
            foreach ($elements as $fqsen) {
                $source = $this->loader->find((string)$fqsen);
                if ($source) {
                    $inheritance += $this->getInheritance($source);
                }
            }

            return $inheritance;
        };

        $inheritance = [$source->getFqsen() => $source];
        $element = $source->getElement();
        foreach (['getUsedTraits', 'getInterfaces', 'getParents', 'getParent'] as $getter) {
            $inheritance += $walker($element, $getter);
        }

        return $inheritance;
    }
}
