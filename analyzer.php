<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;


use RoadRunnerAnalytics\Visitors\EdgeBuilder;
use RoadRunnerAnalytics\GraphFormatters\InheritanceHierarchyFormatter;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\Visitors\FilenameIdResolver;
use RoadRunnerAnalytics\Visitors\NodeBuilder;


ini_set('memory_limit','2G');

$starttime = microtime(true);

$parsedFiles = [];

$codeEdges = array();
$codeNodes = array();

$nodeBuilder = new NodeBuilder(new ClassNameHelper());

$filesToAnalyze = array();


function humanReadable($size)
{
  $unit=array('b','kb','mb','gb','tb','pb');
  return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

echo "Building file list...\n";
while ($f = fgets(STDIN)) {

  $filename     = str_replace(PHP_EOL, '', $f);
  $filePath     = dirname(__FILE__) . '/' . $filename;
  $absolutePath = realpath($filePath);

  $filesToAnalyze[] = $absolutePath;
}

echo "Parsing files...\n";
foreach ($filesToAnalyze as $absolutePath) {
  $code = file_get_contents($absolutePath);

  $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

  try {
    $parsedFiles[$absolutePath] = $parser->parse($code);

  } catch (Exception $e) {
    echo basename($absolutePath) . ":\n";
    echo "\tParse Error: ", $e->getMessage(), "\n";
  }
}

echo "Pass one:\n";
echo "\tResolving names...\n";
echo "\tIdentifying classes...\n";
echo "\tAnalyzing nodes...\n";
foreach ($filesToAnalyze as $absolutePath) {

  $stmts = $parsedFiles[$absolutePath];

  $traverser = new NodeTraverser();
  $nodeBuilder->setFilename($absolutePath);
  $traverser->addVisitor(new NameResolver());
  $traverser->addVisitor(new FilenameIdResolver($absolutePath));
  $traverser->addVisitor($nodeBuilder);
  $parsedFiles[$absolutePath] = $traverser->traverse($stmts);

}

echo "Pass two:\n";
echo "\tAnalyzing edges...\n";
$edgeBuilder = new EdgeBuilder($nodeBuilder->getNodes(), new ClassNameHelper());
foreach ($filesToAnalyze as $absolutePath) {
  $stmts = $parsedFiles[$absolutePath];

  $traverser = new NodeTraverser();
  $edgeBuilder->setFilename($absolutePath);
  $traverser->addVisitor($edgeBuilder);

  $parsedFiles[$absolutePath] = $traverser->traverse($stmts);
}


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

echo "Analysis complete in " . $elapsedTime . " seconds\n";
echo "Analysis required " . humanReadable(memory_get_peak_usage()) . "\n";