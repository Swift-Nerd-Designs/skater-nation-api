<?php

/**
 * CLI error view — required by CodeIgniter\CLI\BaseCommand::showError()
 * when a spark command fails. Without this file, CI4 throws a secondary
 * "Failed to open stream" error that masks the real error message.
 *
 * @var string $title
 * @var string $message
 * @var string $trace
 */

echo "\n";
echo sprintf("\t%s\n", $title ?? 'Error');
echo "\n";
echo sprintf("\t%s\n", $message ?? '');
echo "\n";
if (! empty($trace)) {
    echo sprintf("\t%s\n", $trace);
}
echo "\n";
