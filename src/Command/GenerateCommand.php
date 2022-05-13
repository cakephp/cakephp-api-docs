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

namespace Cake\ApiDocs\Command;

use Cake\ApiDocs\Generator;
use Cake\ApiDocs\Project;
use Cake\ApiDocs\Twig\Extension\ReflectionExtension;
use Cake\ApiDocs\Twig\TwigRuntimeLoader;
use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Twig\Environment;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Loader\FilesystemLoader;

class GenerateCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addArgument('project-path', [
            'required' => true,
            'help' => 'The project root path. Source directories are relative to this.',
        ]);

        $parser->addOption('output-dir', [
            'required' => true,
            'help' => 'The render output directory.',
        ]);

        $parser->addOption('config', [
            'required' => true,
            'help' => 'The config name to use, for example: cakephp4.',
        ]);

        $parser->addOption('version', [
            'required' => true,
            'help' => 'The project version (example: 4.0)',
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        Configure::config('default', new PhpConfig());
        Configure::load($args->getOption('config'), 'default', false);

        $project = new Project($args->getArgumentAt(0), Configure::read('Project'));

        $twig = $this->createTwig(Configure::read('Twig.templateDir'), $project);
        $twig->addGlobal('version', $args->getOption('version'));
        foreach (Configure::read('Twig.globals') as $name => $value) {
            $twig->addGlobal($name, $value);
        }

        $generator = new Generator($twig, $args->getOption('output-dir'));
        $generator->generate($project);

        return static::CODE_SUCCESS;
    }

    /**
     * @param string $templateDir Twig template dirctory
     * @param \Cake\ApiDocs\Project $project Api Project
     * @return \Twig\Environment
     */
    protected function createTwig(string $templateDir, Project $project): Environment
    {
        $twig = new Environment(
            new FilesystemLoader($templateDir),
            ['strict_variables' => true]
        );

        $twig->addRuntimeLoader(new TwigRuntimeLoader());
        $twig->addExtension(new MarkdownExtension());
        $twig->addExtension(new ReflectionExtension($project));

        return $twig;
    }
}
