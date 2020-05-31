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

use Cake\Core\Configure;
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
     * Constructor
     */
    public function __construct()
    {
        $this->outputPath = Configure::read('output');
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        if (!is_dir($this->outputPath)) {
            throw new InvalidArgumentException("Unable to create output directory `{$this->outputPath}`.");
        }

        $this->twig = $this->createTwig(Configure::read('templates'));
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
}
