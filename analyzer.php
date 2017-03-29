<?php

require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bramus\Monolog\Formatter\ColoredLineFormatter;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;


use RoadRunnerAnalytics\Visitors\EdgeBuilder;
use RoadRunnerAnalytics\GraphFormatters\InheritanceHierarchyFormatter;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\Visitors\FilenameIdResolver;
use RoadRunnerAnalytics\Visitors\NodeBuilder;
use RoadRunnerAnalytics\Visitors\SelfResolver;


$logger = new Logger('', [
  (new StreamHandler('php://stdout', Logger::INFO))->setFormatter(new ColoredLineFormatter())
]);

ini_set('memory_limit','2G');

$starttime = microtime(true);

$parsedFiles = [];

$codeEdges = array();
$codeNodes = array();

$nodeBuilder = new NodeBuilder(new ClassNameHelper(), $logger);

$filesToAnalyze = array();


function humanReadable($size)
{
  $unit=array('b','kb','mb','gb','tb','pb');
  return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

$logger->info("Building file list...");
while ($f = fgets(STDIN)) {

  $filename     = str_replace(PHP_EOL, '', $f);
  $filePath     = dirname(__FILE__) . '/' . $filename;
  $absolutePath = realpath($filePath);

  $filesToAnalyze[] = $absolutePath;
}
$fileListTime = microtime(true);
$logger->info("File list complete in " . ($fileListTime - $starttime) . " seconds.");

$logger->info("Parsing files...");
foreach ($filesToAnalyze as $absolutePath) {
  $code = file_get_contents($absolutePath);

  $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

  try {
    $parsedFiles[$absolutePath] = $parser->parse($code);

  } catch (Exception $e) {
    $logger->error(basename($absolutePath) . ":");
    $logger->error("\tParse Error: ", $e->getMessage());
  }
}
$parseTime = microtime(true);
$logger->info("Parsing files complete in " . ($parseTime - $fileListTime) . " seconds.");

$logger->info("Pass one:");
$logger->info("\tResolving names...");
$logger->info("\tIdentifying classes...");
$logger->info("\tAnalyzing nodes...");
foreach ($filesToAnalyze as $absolutePath) {

  $stmts = $parsedFiles[$absolutePath];

  $traverser = new NodeTraverser();
  $nodeBuilder->setFilename($absolutePath);
  $traverser->addVisitor(new NameResolver());
  $traverser->addVisitor(new FilenameIdResolver($absolutePath));
  $traverser->addVisitor(new SelfResolver(new ClassNameHelper()));
  $traverser->addVisitor($nodeBuilder);

  try {
    $parsedFiles[$absolutePath] = $traverser->traverse($stmts);
  } catch (Exception $e) {
    $logger->error(basename($absolutePath) . ":");
    $logger->error("\tParse Error: ", $e->getMessage());
  }


}
$passOneTime = microtime(true);
$logger->info("Pass one complete in " . ($passOneTime - $parseTime) . " seconds.");

$logger->info("Creating external nodes...");
$nodeBuilder->addExternalNodesForUnvisitedReferences();
$nodeTime = microtime(true);
$logger->info("Creating external nodes complete in " . ($nodeTime - $passOneTime) . " seconds.");

$logger->info("Pass two:");
$logger->info("\tAnalyzing edges...");
$edgeBuilder = new EdgeBuilder($nodeBuilder->getNodes(), new ClassNameHelper(), $logger);
foreach ($filesToAnalyze as $absolutePath) {
  $stmts = $parsedFiles[$absolutePath];

  $traverser = new NodeTraverser();
  $edgeBuilder->setFilename($absolutePath);
  $traverser->addVisitor($edgeBuilder);

  $parsedFiles[$absolutePath] = $traverser->traverse($stmts);
}
$passTwoTime = microtime(true);
$logger->info("Pass two complete in " . ($passTwoTime - $nodeTime) . " seconds.");


$js = 'var roadRunnerDeps = ';
$js .= json_encode(array(
                     'edges' => $edgeBuilder->getEdges(),
                     'nodes' => $nodeBuilder->getNodes(),
                     'classInheritanceEdges' => (new InheritanceHierarchyFormatter())->format($nodeBuilder, $edgeBuilder)
                   ));

$jsFilename = dirname(__FILE__) . '/www/js/class-graph.js';
file_put_contents($jsFilename, $js);

$endTime = microtime(true);
$elapsedTime = $endTime - $starttime;

$logger->info("Analysis complete in " . $elapsedTime . " seconds");
$logger->info("Analysis required " . humanReadable(memory_get_peak_usage()));
