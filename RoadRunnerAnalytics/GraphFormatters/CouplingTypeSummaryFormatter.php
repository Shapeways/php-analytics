<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/13/17
 * Time: 11:16 AM
 */

namespace RoadRunnerAnalytics\GraphFormatters;


use Psr\Log\LoggerInterface;
use RoadRunnerAnalytics\Visitors\EdgeBuilderVisitor;
use RoadRunnerAnalytics\Visitors\NodeBuilderVisitor;

class CouplingTypeSummaryFormatter
{

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  public function outputNodes(array $nodes)
  {
    $classes = [];
    $interfaces = [];
    $traits = [];
    $externalDeps = [];

    foreach ($nodes as $nodeId => $node) {

      if ($node[NodeBuilderVisitor::NODE_EXTRA_DATA][NodeBuilderVisitor::NODE_EXTRA_EXTERNAl_ORIGIN]) {
        $externalDeps[] = $node;
      } else {
        switch ($node[NodeBuilderVisitor::NODE_TYPE]) {
          case NodeBuilderVisitor::NODE_TYPE_CLASS:
            $classes[] = $node;
            break;

          case NodeBuilderVisitor::NODE_TYPE_INTERFACE:
            $interfaces[] = $node;
            break;

          case NodeBuilderVisitor::NODE_TYPE_TRAIT:
            $traits[] = $node;
            break;
        }
      }

    }


    $this->logger->info('Classes:');
    foreach ($classes as $class) {
      $this->logger->info("\t" . $class[NodeBuilderVisitor::NODE_NAME]);
    }

    $this->logger->info('Interfaces:');
    foreach ($interfaces as $interface) {
      $this->logger->info("\t" . $interface[NodeBuilderVisitor::NODE_NAME]);
    }

    $this->logger->info('Traits:');
    foreach ($traits as $trait) {
      $this->logger->info("\t" . $trait[NodeBuilderVisitor::NODE_NAME]);
    }

    $this->logger->info('External references:');
    foreach ($externalDeps as $external) {
      $this->logger->info("\t" . $external[NodeBuilderVisitor::NODE_NAME]);
    }

  }

  public function outputEdges(array $edges, array $nodes) {

    $tightCouplings = [];
    $looseCouplings = [];

    foreach ($edges as $edge) {
      if (
        ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_EXTENDS)
        || ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_TRAIT_USE)
        || ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_STATIC_ACCESS)
        || ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_INSTANCEOF)
        || ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_CONST_FETCH)
        || ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_IMPLEMENTS)
        || ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_INSTANTIATES)
      ) {
        $tightCouplings[] = $edge;
      }
      else if (
        ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_METHOD_PARAM)
      )
      {
        $looseCouplings[] = $edge;
      }
    }


    $this->logger->info("\tTight couplings:");
    foreach ($tightCouplings as $tightCoupling) {
      $verb = 'is tightly coupled to';

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_EXTENDS) {
        $verb = 'extends';
      }

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_TRAIT_USE) {
        $verb = 'uses trait';
      }

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_STATIC_ACCESS) {
        $verb = 'accesses static properties of';
      }

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_INSTANCEOF) {
        $verb = 'instance of type comparison';
      }

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_CONST_FETCH) {
        $verb = 'accesses class constants of';
      }

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_IMPLEMENTS) {
        $verb = 'implements interface';
      }

      if ($tightCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_INSTANTIATES) {
        $verb = 'instantiates class';
      }

      $sourceNode = $nodes[$tightCoupling[EdgeBuilderVisitor::EDGE_SOURCE]];
      $targetNode = $nodes[$tightCoupling[EdgeBuilderVisitor::EDGE_TARGET]];

      $this->logger->info("\t\t" . $sourceNode[NodeBuilderVisitor::NODE_NAME] . '(' . $sourceNode[NodeBuilderVisitor::NODE_TYPE] . ") $verb " . $targetNode[NodeBuilderVisitor::NODE_NAME] . '(' . $targetNode[NodeBuilderVisitor::NODE_TYPE] . ')');
    }

    $this->logger->info("\tLoose couplings:");
    foreach ($looseCouplings as $looseCoupling) {
      $verb = 'is loosely coupled to';

      if ($looseCoupling[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_METHOD_PARAM) {
        $verb = 'accepts as a method parameter';
      }

      $sourceNode = $nodes[$looseCoupling[EdgeBuilderVisitor::EDGE_SOURCE]];
      $targetNode = $nodes[$looseCoupling[EdgeBuilderVisitor::EDGE_TARGET]];

      $this->logger->info("\t\t" . $sourceNode[NodeBuilderVisitor::NODE_NAME] . '(' . $sourceNode[NodeBuilderVisitor::NODE_TYPE] . ") $verb " . $targetNode[NodeBuilderVisitor::NODE_NAME] . '(' . $targetNode[NodeBuilderVisitor::NODE_TYPE] . ')');
    }

  }

}