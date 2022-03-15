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

namespace Cake\ApiDocs\Command;

use Cake\ApiDocs\Generator;
use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

class GenerateCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addArgument('project-path', [
            'required' => true,
            'help' => 'The project root path.',
        ]);

        $parser->addArgument('output-path', [
            'required' => true,
            'help' => 'The html output path.',
        ]);

        $parser->addOption('config', [
            'required' => true,
            'help' => 'Config name.',
        ]);

        $parser->addOption('version', [
            'required' => true,
            'help' => 'The current version.',
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->configure($args);

        $generator = new Generator($args->getArgumentAt(0), $args->getArgumentAt(1), Configure::read('templatePath'));
        $generator->generate();

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\Console\Arguments $args The command line arguments
     * @return void
     */
    protected function configure(Arguments $args): void
    {
        Configure::config('default', new PhpConfig());
        Configure::load($args->getOption('config'), 'default', false);

        $basePath = $args->getArgumentAt(0);
        $config = $args->getOption('config');
        $optionVersion = $args->getOption('version');
        Configure::write('config', $args->getOption('config'));
        Configure::write('basePath', $basePath);

        if (in_array($config, ['chronos', 'elastic'])) {
            // Chronos and elastic plugin should use the X.Y branch instead of exact tag version
            Configure::write('version', $optionVersion);
        } elseif ($config === 'queue') {
            // Queue plugin doesn't have a 0.x branch and can't read tag from CLI
            Configure::write('version', 'master');
        } else {
            $gitTagVersion = $optionVersion;
            $gitTag = shell_exec('cd ' . $basePath . ' && git describe');
            if (is_string($gitTag)) {
                $gitTagVersion = explode('-', $gitTag)[0];
                $gitTagVersion = preg_replace("/\r|\n/", '', $gitTagVersion);
            }
            Configure::write('version', $gitTagVersion);
        }
    }
}
