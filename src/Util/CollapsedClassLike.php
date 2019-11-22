<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Util;

/**
 * CollapsedClassLike
 */
class CollapsedClassLike
{
    /**
     * @var \Cake\ApiDocs\Util\LoadedFqsen
     */
    protected $source;

    /**
     * @var array
     */
    protected $constants = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @param \Cake\ApiDocs\Util\LoadedFqsen $source loaded fsqen
     * @param array $constants constants
     * @param array $properties properties
     * @param array $methods methods
     */
    public function __construct(LoadedFqsen $source, array $constants, array $properties, array $methods)
    {
        $this->source = $source;
        $this->constants = $constants;
        $this->properties = $properties;
        $this->methods = $methods;
    }

    /**
     * @return \Cake\ApiDocs\Util\LoadedFqsen
     */
    public function getSource(): LoadedFqsen
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}
