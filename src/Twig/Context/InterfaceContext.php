<?php
declare(strict_types=1);

namespace App\Twig\Context;

class InterfaceContext extends ClassLikeContext
{
    /**
     * @var string
     */
    public const CLASS_TYPE = 'interface';
}
