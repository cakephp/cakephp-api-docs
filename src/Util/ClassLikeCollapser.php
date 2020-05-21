<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Util;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Constant;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Property;
use phpDocumentor\Reflection\Php\Trait_;
use phpDocumentor\Reflection\Php\Visibility;
use phpDocumentor\Reflection\Types\Mixed_;

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

        $inheritance = $this->getInheritanceChain($source);

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
                    $source = $this->loader->find((string)$fqsen);
                    if ((string)$source->getElement()->getVisibility() !== 'private') {
                        $elements[$element->getName()][] = $source;
                    }
                }
            }
        }
        ksort($elements);

        foreach ($elements as &$sources) {
            $docBlock = $this->collapseDocBlock($sources);
            $docBlock = $this->validateDocBlock($sources[0], $docBlock);
            $sources = ['source' => $sources[0], 'docBlock' => $docBlock];
        }

        return $elements;
    }

    /**
     * Validates docblock against element source and adds placeholders where needed.
     *
     * @param \Cake\ApiDocs\Util\LoadedFqsen $source source
     * @param \phpDocumentor\Reflection\DocBlock $docBlock docblock
     * @return \phpDocumentor\Reflection\DocBlock
     */
    protected function validateDocBlock(LoadedFqsen $source, DocBlock $docBlock): DocBlock
    {
        foreach ($docBlock->getTags() as $tag) {
            if ($tag instanceof InvalidTag) {
                if ($source->getInProject()) {
                    api_log('warning', "Found invalid @{$tag->getName()} for `{$source->getFqsen()}`.");
                }
                $docBlock->removeTag($tag);
            }
        }

        $addedTags = [];
        if ($source->getElement() instanceof Constant || $source->getElement() instanceof Property) {
            $tags = $docBlock->getTagsByName('var');
            if (count($tags) === 0) {
                if ($source->getInProject()) {
                    api_log('warning', "Missing @var for `{$source->getFqsen()}`.");
                }
                $addedTags[] = new Var_($source->getElement()->getName(), new Mixed_());
            }
        }

        if ($source->getElement() instanceof Method) {
            $tags = $docBlock->getTagsByName('param');
            foreach ($source->getElement()->getArguments() as $argument) {
                $matchedTag = false;
                foreach ($tags as $tag) {
                    if ($tag->getVariableName() === $argument->getName()) {
                        $matchedTag = true;
                        break;
                    }
                }
                if (!$matchedTag) {
                    if (
                        $source->getInProject() &&
                        !($source->getParent() instanceof Trait_ && $this->isDocBockInheriting($docBlock))
                    ) {
                        api_log('warning', "Missing @param for `{$argument->getName()}` in `{$source->getFqsen()}`.");
                    }
                    $addedTags[] = new Param($argument->getName(), $argument->getType(), $argument->isVariadic());
                }
            }
        }

        if (count($addedTags) > 0) {
            $docBlock = new DocBlock(
                $docBlock->getSummary(),
                $docBlock->getDescription(),
                array_merge($addedTags, $docBlock->getTags()),
                $docBlock->getContext(),
                $docBlock->getLocation(),
                $docBlock->isTemplateStart(),
                $docBlock->isTemplateEnd()
            );
        }

        return $docBlock;
    }

    /**
     * Collapsed doc blocks for element.
     *
     * @param \Cake\ApiDocs\Util\LoadedFqsen[] $sources sources
     * @return \phpDocumentor\Reflection\DocBlock
     */
    protected function collapseDocBlock(array $sources): DocBlock
    {
        $summary = '';
        $description = '';
        $description_tags = [];
        $tags = [];
        foreach ($sources as $source) {
            /** @var \phpDocumentor\Reflection\DocBlock $docBlock */
            $docBlock = $source->getElement()->getDocBlock();
            if (!$docBlock) {
                break;
            }

            $summary = $docBlock->getSummary();
            $description = $docBlock->getDescription()->getBodyTemplate() . ($description ? '' : "\n{$description}");
            $description_tags = array_merge($description_tags, $docBlock->getDescription()->getTags());
            $tags = array_merge($tags, $docBlock->getTags());

            if (!$this->isDocBockInheriting($docBlock)) {
                break;
            }
        }

        return new DocBlock($summary, new Description($description, $description_tags), $tags);
    }

    /**
     * Builds ordered dictionary of collapsed inheritance tree.
     *
     * @param \Cake\ApiDocs\Util\LoadedFqsen $source loaded fqsen
     * @return \Cake\ApiDocs\Util\LoadedFqsen[]
     */
    protected function getInheritanceChain(LoadedFqsen $source): array
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
                    $inheritance += $this->getInheritanceChain($source);
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

    /**
     * Checks if a docblock is inheriting.
     *
     * @param \phpDocumentor\Reflection\DocBlock $docblock docblock
     * @return bool
     */
    protected function isDocBockInheriting(DocBlock $docBlock): bool
    {
        foreach ($docBlock->getTags() as $tag) {
            if (preg_match('/inheritDoc/i', $tag->getName()) === 1) {
                return true;
            }
        }
        if (preg_match('/@inheritDoc/i', $docBlock->getSummary()) === 1) {
            return true;
        }

        return false;
    }
}
