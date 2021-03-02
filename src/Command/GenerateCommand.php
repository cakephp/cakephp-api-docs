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
        $parser->addArgument('source', [
            'required' => true,
            'help' => 'The source base path.',
        ]);

        $parser->addOption('config', [
            'required' => true,
            'help' => 'Config file.',
        ]);

        $parser->addOption('version', [
            'required' => true,
            'help' => 'The current version.',
        ]);

        $parser->addOption('output', [
            'required' => true,
            'help' => 'The html output path.',
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->configure($args);

        $projectPath = $args->getArgumentAt(0);
        $generator = new Generator($projectPath);
        $generator->generateAll();

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

        Configure::write('output', $args->getOption('output'));
        Configure::write('globals.version', $args->getOption('version'));
    }
}
