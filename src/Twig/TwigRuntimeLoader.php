<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Twig;

use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigRuntimeLoader implements RuntimeLoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(string $class)
    {
        if ($class === MarkdownRuntime::class) {
            return new MarkdownRuntime(new DefaultMarkdown());
        }
    }
}
