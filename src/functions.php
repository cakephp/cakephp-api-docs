<?php
declare(strict_types=1);

use Cake\Log\Log;

/**
 * Logs messages.
 *
 * @param string $level Log level
 * @param string $message Log message
 * @param array $context Log context
 */
function api_log(string $level, string $message, array $context = [])
{
    Log::{$level}($message, $context);
}
