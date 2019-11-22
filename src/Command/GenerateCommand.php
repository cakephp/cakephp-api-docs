<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Command;

use Cake\ApiDocs\Generator;
use Cake\ApiDocs\Util\SourceLoader;
use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;

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

        $parser->addOption('template-dir', [
            'required' => true,
            'help' => 'The twig template path.',
        ]);

        $parser->addOption('output-dir', [
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
        $config = $this->loadConfig($args);

        $sourcePath = $args->getArgumentAt(0);
        $loader = new SourceLoader($sourcePath);
        $generator = new Generator($loader, $config);
        $generator->generate();

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\Console\Arguments $args The command line arguments
     * @return array
     */
    protected function loadConfig(Arguments $args): array
    {
        $config = FileSystem::read($args->getOption('config'));
        $config = Neon::decode($config);
        $config = array_merge($config, [
            'templateDir' => $args->getOption('template-dir'),
            'outputDir' => $args->getOption('output-dir'),
        ]);
        $config['globals'] = array_merge($config['globals'] ?? [], [
            'version' => $args->getOption('version'),
        ]);

        return $config;
    }
}
