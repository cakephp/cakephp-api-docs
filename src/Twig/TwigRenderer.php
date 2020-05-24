<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ApiDocs\Twig;

use InvalidArgumentException;
use Twig\Environment;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $outputPath;

    /**
     * @param string $templatesPath The path containing twig templates
     * @param string $outputPath The render output path
     */
    public function __construct(string $templatesPath, string $outputPath)
    {
        $this->outputPath = $outputPath;
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        if (!is_dir($this->outputPath)) {
            throw new InvalidArgumentException("Unable to create output directory {$outputPath}");
        }

        $this->twig = $this->createTwig($templatesPath);
    }

    /**
     * Returns Twig Environment.
     *
     * @return \Twig\Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * @param string $template The twig template name in template path
     * @param string $filename The output filename
     * @param array $context The twig render context
     * @return void
     */
    public function render(string $template, string $filename, array $context = []): void
    {
        $path = getcwd() . DS . $this->outputPath . DS . $filename;
        $file = fopen($path, 'wb');
        try {
            fwrite($file, $this->twig->render($template, $context));
        } catch (\Error $e) {
            api_log('error', "Unable to render {$filename}.");
            throw $e;
        }
        fclose($file);
    }

    /**
     * @param string $templatesPath Twig template directory
     * @return \Twig\Environment
     */
    protected function createTwig(string $templatesPath): Environment
    {
        $twig = new Environment(
            new FilesystemLoader($templatesPath)
        );

        $twig->addExtension(new MarkdownExtension());
        $twig->addRuntimeLoader(new TwigRuntimeLoader());

        return $twig;
    }

    /*
    private function addFilters(): void
    {
        $this->twig->addFilter(new TwigFilter('fqsen', function (string $fqsen) {
            return substr($fqsen, 1);
        }));

        $this->twig->addFilter(new TwigFilter('name', function (string $fqsen, bool $strip = false) {
            $parts = explode('::', $fqsen);
            if (count($parts) > 1) {
                $name = end($parts);
                if ($strip) {
                    if ($name[0] === '$') {
                        $name = substr($name, 1);
                    }
                    if ($name[-1] === ')') {
                        $name = substr($name, 0, -2);
                    }
                }

                return $name;
            }

            return substr($fqsen, strrpos($fqsen, '\\') + 1);
        }));
    }

    private function addTests(): void
    {
        $this->twig->addTest(new TwigTest('startOf', function (string $start, ?string $whole) {
            return Strings::startsWith($whole ?? '', $start);
        }));
    }
    */
}
