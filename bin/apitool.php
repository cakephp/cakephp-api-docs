#!/usr/local/bin/php -q
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\ApiDocs\Application;
use Cake\Console\CommandRunner;

// Build the runner with an application and root executable name.
$runner = new CommandRunner(new Application(), 'apitool');
exit($runner->run($argv));
