<?php
declare(strict_types=1);

namespace App\Twig\Context;

use App\Loader;
use phpDocumentor\Reflection\Php\Constant;

class ConstantContext extends ElementContext
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
     * @var \phpDocumentor\Reflection\Php\Constant
     */
    protected $element;

    /**
     * @param \App\Loader $loader The fqsen loader
     * @param string $fqsen The constant fqsen
     * @param \phpDocumentor\Reflection\Php\Constant $constant The constant element
     */
    public function __construct(Loader $loader, string $fqsen, Constant $constant)
    {
        parent::__construct($fqsen, $constant);
        $this->loader = $loader;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->element->getValue();
    }
}
