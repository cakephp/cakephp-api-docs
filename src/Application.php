<?php
declare(strict_types=1);

namespace Cake\ApiDocs;

use Cake\ApiDocs\Command\GenerateCommand;
use Cake\Console\CommandCollection;
use Cake\Core\ConsoleApplicationInterface;

class Application implements ConsoleApplicationInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
    }

    /**
     * Define the console commands for an application.
     *
     * @param \Cake\Console\CommandCollection $commands The CommandCollection to add commands into.
     * @return \Cake\Console\CommandCollection The updated collection.
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('generate', GenerateCommand::class);

        return $commands;
    }
}
