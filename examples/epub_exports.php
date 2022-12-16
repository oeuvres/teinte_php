<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log, LoggerCli};
use Oeuvres\Teinte\Format\{Epub};

Log::setLogger(new LoggerCli(LogLevel::DEBUG));
if (count($argv) < 2) {
    return Log::warning("A filename is waited as an argument");
}
$src_file = $argv[1];
$dst_dir = __DIR__ . "/out/";
$dst_name = pathinfo($src_file, PATHINFO_FILENAME);

$epub = new Epub();
$epub->load($src_file);
Log::debug('start');
file_put_contents($dst_dir . $dst_name . ".html", $epub->html());
Log::debug('html');
file_put_contents($dst_dir . $dst_name . ".xml", $epub->tei());
Log::debug('tei');
