<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Util;

use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Php\File;

/**
 * LoadedFqsen
 */
class LoadedFqsen
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $fqsen;

    /**
     * @var \phpDocumentor\Reflection\Element
     */
    protected $element;

    /**
     * @var \phpDocumentor\Reflection\Element|null
     */
    protected $parent;

    /**
     * @var \phpDocumentor\Reflection\Php\File|null
     */
    protected $file;

    /**
     * @var bool
     */
    protected $inProject;

    /**
     * @param string $fqsen fqsen
     * @param \phpDocumentor\Reflection\Element $element reflection element
     * @param \phpDocumentor\Reflection\Element|null $parent parent reflection element
     * @param \phpDocumentor\Reflection\Php\File|null $file reflection file
     * @param bool $inProject Whether fqsen is in project
     */
    public function __construct(string $fqsen, Element $element, ?Element $parent, ?File $file, bool $inProject)
    {
        $this->fqsen = $fqsen;
        $this->element = $element;
        $this->parent = $parent;
        $this->file = $file;
        $this->inProject = $inProject;

        $parts = explode('::', $fqsen);
        if (count($parts) > 1) {
            $this->name = end($parts);
        } else {
            $this->name = substr($fqsen, strrpos($fqsen, '\\') + 1);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFqsen(): string
    {
        return $this->fqsen;
    }

    /**
     * @return \phpDocumentor\Reflection\Element
     */
    public function getElement(): Element
    {
        return $this->element;
    }

    /**
     * @return \phpDocumentor\Reflection\Element|null
     */
    public function getParent(): ?Element
    {
        return $this->parent;
    }

    /**
     * @return \phpDocumentor\Reflection\Php\File
     */
    public function getFile(): ?File
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getInProject(): bool
    {
        return $this->inProject;
    }
}
