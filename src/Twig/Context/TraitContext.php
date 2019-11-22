<?php
declare(strict_types=1);

namespace App\Twig\Context;

class TraitContext extends ClassLikeContext
{
    /**
     * @var string
     */
    public const CLASS_TYPE = 'trait';
}
