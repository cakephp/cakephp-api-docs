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

use Cake\ApiDocs\Twig\Extension\ReflectionExtension;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use InvalidArgumentException;
use RuntimeException;
use Twig\Environment;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    use LogTrait;

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
     */
    public function __construct(string $outputPath)
    {
        $this->outputPath = $outputPath;
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        if (!is_dir($this->outputPath)) {
            throw new InvalidArgumentException("Unable to create output directory `{$this->outputPath}`.");
        }

        $this->createTwig(Configure::read('templates'));
        $this->twig->addGlobal('config', Configure::read('globals'));
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
     * Sets Twig global.
     *
     * @param string $name global name
     * @param mixed $value global value
     * @return void
     */
    public function setGlobal(string $name, $value): void
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
     * @param string $templatesPath Twig template directory
     * @return void
     */
    protected function createTwig(string $templatesPath): void
    {
        $this->twig = new Environment(
            new FilesystemLoader($templatesPath)
        );

        $this->twig->addRuntimeLoader(new TwigRuntimeLoader());
        $this->twig->addExtension(new MarkdownExtension());
        $this->twig->addExtension(new ReflectionExtension());
    }
}
