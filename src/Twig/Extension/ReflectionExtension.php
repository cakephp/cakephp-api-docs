<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Twig\Extension;

use Cake\ApiDocs\Util\LoadedFqsen;
use Cake\ApiDocs\Util\SourceLoader;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Constant;
use phpDocumentor\Reflection\Php\Function_;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Trait_;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
            new TwigFilter('to_path', function ($source) {
                if ($source instanceof Element) {
                    $fqsen = (string)$source->getFqsen();
                } elseif ($source instanceof LoadedFqsen) {
                    $fqsen = (string)$source->getElement()->getFqsen();
                } else {
                    $fqsen = (string)$source;
                }

                return substr($fqsen, 1);
            }),
            new TwigFilter('to_name', function ($source) {
                if ($source instanceof Element) {
                    $fqsen = (string)$source->getFqsen();
                } elseif ($source instanceof LoadedFqsen) {
                    $fqsen = (string)$source->getElement()->getFqsen();
                } else {
                    $fqsen = (string)$source;
                }

                $name = explode('::', substr($fqsen, strrpos($fqsen, '\\') + 1));

                return preg_replace('/[\$\(\)]/', '', end($name));
            }),
            new TwigFilter('to_namespace', function ($source) {
                if ($source instanceof Element) {
                    $fqsen = (string)$source->getFqsen();
                } elseif ($source instanceof LoadedFqsen) {
                    $fqsen = (string)$source->getElement()->getFqsen();
                } else {
                    $fqsen = (string)$source;
                }

                return substr($fqsen, 0, strrpos($fqsen, '\\'));
            }),
            new TwigFilter('to_url', function ($source) {
                if ($source instanceof Element) {
                    $source = (string)$source->getFqsen();
                }
                if (!($source instanceof LoadedFqsen)) {
                    $source = $this->loader->find((string)$source);
                }
                if ($source === null) {
                    return '';
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
                $name = preg_replace(['/\\\\/', '/[\$\(\)]/'], ['.', ''], $parts[0]);
                $anchor = preg_replace('/[\$\(\)]/', '', count($parts) == 2 ? $parts[1] : '');

                $url = "{$type}-{$name}.html";
                if ($anchor) {
                    $url .= "#{$anchor}";
                }

                return $url;
            }),
            new TwigFilter('ns_to_url', function ($source) {
                if ($source instanceof Element) {
                    $source = (string)$source->getFqsen();
                }

                $name = str_replace('\\', '.', substr((string)$source, 1));
                $url = "namespace-{$name}.html";

                return $url;
            }),
            new TwigFilter('docblock', function ($source) {
                if ($source instanceof Element) {
                    return $source->getDocBlock() ?? new DocBlock();
                }
                if (!($source instanceof LoadedFqsen)) {
                    $source = $this->loader->find((string)$source);

                    return $source->getElement()->getDocBlock() ?? new DocBlock();
                }

                throw new InvalidArgumentException("Could not find {$source}.");
            }),
            new TwigFilter('tags', function ($source, $name = null, $single = false) {
                if (!($source instanceof DocBlock)) {
                    if ($source instanceof Element) {
                        $source = (string)$source->getFqsen();
                    }
                    if (!($source instanceof LoadedFqsen)) {
                        $source = $this->loader->find((string)$source);
                    }
                    $source = $source->getElement()->getDocBlock() ?? new DocBlock();
                }

                $tags = [];
                foreach ($source->getTags() as $tag) {
                    if ($tag->getName() === $name) {
                        $tags[] = $tag;
                    }
                }

                return $single ? (empty($tags) ? null : $tags[0]) : $tags;
            }),
            new TwigFilter('param', function ($source, $name) {
                if (!($source instanceof DocBlock)) {
                    if ($source instanceof Element) {
                        $source = (string)$source->getFqsen();
                    }
                    if (!($source instanceof LoadedFqsen)) {
                        $source = $this->loader->find((string)$source);
                    }
                    $source = $source->getElement()->getDocBlock() ?? new DocBlock();
                }

                $params = $source->getTagsByName('param');
                foreach ($params as $param) {
                    if ($param->getVariableName() === $name) {
                        return $param;
                    }
                }

                throw new InvalidArgumentException("Function does not have `{$name}` argument.");
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
                if ($source instanceof Element) {
                    $source = (string)$source->getFqsen();
                }

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
