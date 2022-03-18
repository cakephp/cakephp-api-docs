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

        $parser->addOption('tag', [
            'required' => true,
            'help' => 'The tag name.',
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->configure($args);

        $generator = new Generator($args->getArgumentAt(0), $args->getArgumentAt(1));
        $generator->render();

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

        Configure::write('basePath', $args->getArgumentAt(0));
        Configure::write('config', $args->getOption('config'));
        Configure::write('tag', $args->getOption('tag'));
        Configure::write('version', $args->getOption('version'));
    }
}
