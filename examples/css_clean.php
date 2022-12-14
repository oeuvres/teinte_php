<?php declare(strict_types=1);

/**
 * Example usage of the CSS parser to get a light model
 */

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log, LoggerCli};
use Oeuvres\Teinte\Format\{CssFilter};

Log::setLogger(new LoggerCli(LogLevel::DEBUG));
if (count($argv) < 2) {
    return Log::warning("A filename is waited as an argument");
}
$src_file = $argv[1];

$css = new CssFilter();
$css->load($src_file);
print_r($css->model());

