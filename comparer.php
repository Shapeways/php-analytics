<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/12/17
 * Time: 3:02 PM
 */

require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bramus\Monolog\Formatter\ColoredLineFormatter;
use RoadRunnerAnalytics\GraphFormatters\CouplingTypeSummaryFormatter;


ini_set('memory_limit','2G');

/**
 * Get CLI options
 */
$options = getopt('', [
  'masterdir::',
  'branchdir::'
]);

/**
 * Set up logger
 */
$logger = new Logger('', [
  (new StreamHandler('php://stdout', Logger::INFO))->setFormatter(new ColoredLineFormatter())
]);

$logger->info('Comparing ' . $options['branchdir'] . ' to ' . $options['masterdir']);

$masterNodesFile = $options['masterdir'] . '/class-nodes.json';
$branchNodesFile = $options['branchdir'] . '/class-nodes.json';


$masterNodesJson = file_get_contents($masterNodesFile);
$branchNodesJson = file_get_contents($branchNodesFile);


$masterNodes = json_decode($masterNodesJson, true);
$branchNodes = json_decode($branchNodesJson, true);


$nodesDifference = array_diff_assoc($masterNodes, $branchNodes);
$nodesDifference2 = array_diff_assoc($branchNodes, $masterNodes);

//var_dump($nodesDifference);
//var_dump($nodesDifference2);


$masterEdgesFile = $options['masterdir'] . '/class-edges.json';
$branchEdgesFile = $options['branchdir'] . '/class-edges.json';


$masterEdgesJson = file_get_contents($masterEdgesFile);
$branchEdgesJson = file_get_contents($branchEdgesFile);


$masterEdges = json_decode($masterEdgesJson, true);
$branchEdges = json_decode($branchEdgesJson, true);


$edgesDifference = array_diff_assoc($masterEdges, $branchEdges);
$edgesDifference2 = array_diff_assoc($branchEdges, $masterEdges);


$formatter = new CouplingTypeSummaryFormatter($logger);

echo "\n\n";

$logger->info('Removed from master:');
$formatter->outputNodes($nodesDifference);

echo "\n";

$logger->info('Couplings removed from master:');
$formatter->outputEdges($edgesDifference, $masterNodes);

echo "\n\n";

$logger->info('Dependencies added to branch:');
$formatter->outputNodes($nodesDifference2);

echo "\n";

$logger->info('Couplings added to branch:');
$formatter->outputEdges($edgesDifference2, $branchNodes);
