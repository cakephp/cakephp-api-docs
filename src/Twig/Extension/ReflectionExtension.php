<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Twig\Extension;

use Cake\ApiDocs\Util\LoadedFqsen;
use Cake\ApiDocs\Util\SourceLoader;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Constant;
use phpDocumentor\Reflection\Php\Function_;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Namespace_;
use phpDocumentor\Reflection\Php\Trait_;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * ReflectionExtension
 */
class ReflectionExtension extends AbstractExtension
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
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('to_name', function ($fqsen) {
                return substr((string)$fqsen, 1);
            }),
            new TwigFilter('to_url', function ($source) {
                if (!($source instanceof LoadedFqsen)) {
                    $source = $this->loader->find((string)$source);
                }

                $type = '';
                $linkElement = $source->getParent() ?? $source->getElement();
                if ($linkElement instanceof Class_) {
                    $type = 'class';
                } elseif ($linkElement instanceof Interface_) {
                    $type = 'interface';
                } elseif ($linkElement instanceof Trait_) {
                    $type = 'trait';
                } elseif ($linkElement instanceof Function_) {
                    $type = 'function';
                } elseif ($linkElement instanceof Constant) {
                    $type = 'constant';
                }

                $parts = explode('::', substr($source->getFqsen(), 1), 2);
                $name = str_replace('\\', '.', str_replace('()', '', $parts[0]));
                $anchor = count($parts) == 2 ? $parts[1] : '';

                $url = "{$type}-{$name}.html";
                if ($anchor) {
                    $url .= "#{$anchor}";
                }

                return $url;
            }),
            new TwigFilter('ns_to_url', function ($namespace) {
                $name = str_replace('\\', '.', substr((string)$namespace, 1));
                $url = "namespace-{$name}.html";

                return $url;
            }),
            new TwigFilter('docblock', function ($source) {
                if (!($source instanceof LoadedFqsen)) {
                    $source = $this->loader->find((string)$source);
                }

                return $source->getElement()->getDocBlock();
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTests()
    {
        return [
            new TwigTest('in_project', function ($source) {
                if (!($source instanceof LoadedFqsen)) {
                    $source = $this->loader->find((string)$source);
                }

                return $source->getInProject();
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
        ];
    }
}
