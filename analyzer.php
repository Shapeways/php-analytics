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


$codeEdges = array();
$codeNodes = array();

$nodeBuilder = new NodeBuilder(new ClassNameHelper());

$filesToAnalyze = array();

echo "Building file list...\n";
while ($f = fgets(STDIN)) {

  $filename     = str_replace(PHP_EOL, '', $f);
  $filePath     = dirname(__FILE__) . '/' . $filename;
  $absolutePath = realpath($filePath);

  $filesToAnalyze[] = $absolutePath;
}


echo "Analyzing nodes...\n";
foreach ($filesToAnalyze as $absolutePath) {
  $code = file_get_contents($absolutePath);

  $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

  try {
    $stmts = $parser->parse($code);

    $traverser = new NodeTraverser();
    $nodeBuilder->setFilename($absolutePath);
    $traverser->addVisitor(new NameResolver());
    $traverser->addVisitor(new FilenameIdResolver($absolutePath));
    $traverser->addVisitor($nodeBuilder);
    $stmts = $traverser->traverse($stmts);

  } catch (Exception $e) {
    echo basename($absolutePath) . ":\n";
    echo "\tParse Error: ", $e->getMessage(), "\n";
  }
}

echo "Analyzing edges...\n";
$edgeBuilder = new EdgeBuilder($nodeBuilder->getNodes(), new ClassNameHelper());
foreach ($filesToAnalyze as $absolutePath) {
  $code = file_get_contents($absolutePath);

  $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

  try {
    $stmts = $parser->parse($code);

    $traverser = new NodeTraverser();
    $edgeBuilder->setFilename($absolutePath);
    $traverser->addVisitor(new NameResolver());
    $traverser->addVisitor(new FilenameIdResolver($absolutePath));
    $traverser->addVisitor($edgeBuilder);
    $stmts = $traverser->traverse($stmts);

  } catch (Exception $e) {
    echo basename($absolutePath) . ":\n";
    echo "\tParse Error: ", $e->getMessage(), "\n";
  }
}


$js = 'var roadRunnerDeps = ';
$js .= json_encode(array(
                     'edges' => $edgeBuilder->getEdges(),
                     'nodes' => $nodeBuilder->getNodes(),
                     'classInheritanceEdges' => (new InheritanceHierarchyFormatter())->format($nodeBuilder, $edgeBuilder)
                   ));

$jsFilename = dirname(__FILE__) . '/www/js/class-graph.js';
file_put_contents($jsFilename, $js);

