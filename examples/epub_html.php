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

$epub = new Epub();
$html_file = __DIR__ . "/out/" . pathinfo($src_file, PATHINFO_FILENAME) . ".html";
Filesys::mkdir(dirname($html_file));
$epub->load($src_file);
file_put_contents($html_file, $epub->html());


/*

$logger = new LoggerCli(LogLevel::DEBUG);
$source = new SourceHtml($logger);
$css_file = __DIR__ . '/html_dirty/epub_mscalibre.css';
$css = file_get_contents($css_file);
print_r(SourceHtml::css_semantic($css));
$src_glob = "C:/src/ricardo/epub_DIgEco/*.epub";
$dst_dir = "C:/src/ricardo/opf/";
File::mkdir($dst_dir);
foreach(glob($src_glob) as $epub_file)
{
    SourceHtml::unzip_glob($epub_file, "/\.ncx$/", $dst_dir);
}
*/