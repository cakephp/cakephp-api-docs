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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ApiDocs\Twig;

use Cake\ApiDocs\Twig\Extension\ReflectionExtension;
use RuntimeException;
use Twig\Environment;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @param string $outputPath Output path
     * @param string $templatePath Twig template path
     */
    public function __construct(string $outputPath, string $templatePath)
    {
        $this->outputPath = $outputPath;

        $this->createTwig($templatePath);
    }

    /**
     * Adds a Twig global.
     *
     * @param string $name Global name
     * @param mixed $value Global value
     * @return void
     */
    public function addGlobal(string $name, $value): void
    {
        $this->twig->addGlobal($name, $value);
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
        if ($file === false) {
            throw new RuntimeException("Unable to open `$path` to render template.");
        }
        fwrite($file, $this->twig->render($template, $context));
        fclose($file);
    }

    /**
     * @param string $templatePath Twig template path
     * @return void
     */
    protected function createTwig(string $templatePath): void
    {
        $this->twig = new Environment(
            new FilesystemLoader($templatePath),
            ['strict_variables' => true]
        );

        $this->twig->addRuntimeLoader(new TwigRuntimeLoader());
        $this->twig->addExtension(new MarkdownExtension());
        $this->twig->addExtension(new ReflectionExtension());
    }
}
