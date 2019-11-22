<?php
declare(strict_types=1);

namespace App\Twig\Context;

class ClassContext extends ClassLikeContext
{
    /**
     * @var string
     */
    public const CLASS_TYPE = 'class';

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

        return $modifiers;
    }
}
