<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/17/17
 * Time: 5:52 PM
 */

namespace RoadRunnerAnalytics\GraphFormatters;


use RoadRunnerAnalytics\Visitors\EdgeBuilderVisitor;
use RoadRunnerAnalytics\Visitors\NodeBuilderVisitor;

class InheritanceHierarchyFormatter
{

  const ROOT = '\\';

  /**
   * @param \RoadRunnerAnalytics\Visitors\NodeBuilderVisitor $nodeBuilder
   * @param \RoadRunnerAnalytics\Visitors\EdgeBuilderVisitor $edgeBuilder
   * @return array
   */
  public function format(NodeBuilderVisitor $nodeBuilder, EdgeBuilderVisitor $edgeBuilder) {
    $graph = array(
      array(
        EdgeBuilderVisitor::EDGE_SOURCE => self::ROOT,
        EdgeBuilderVisitor::EDGE_TARGET => '',
        EdgeBuilderVisitor::EDGE_TYPE => EdgeBuilderVisitor::EDGE_TYPE_EXTENDS
      )
    );

    $nodes = $nodeBuilder->getNodes();
    $edges = $edgeBuilder->getEdges();

    $targets = array();
    $sources = array();
    $inheritanceEdges = array();

    $allowedNodeTypes = array(
      NodeBuilderVisitor::NODE_TYPE_INTERFACE,
      NodeBuilderVisitor::NODE_TYPE_CLASS,
      NodeBuilderVisitor::NODE_TYPE_TRAIT
    );

    // catalog all inheritance edges
    foreach ($edges as $edge) {
      if ($edge[EdgeBuilderVisitor::EDGE_TYPE] === EdgeBuilderVisitor::EDGE_TYPE_EXTENDS) {
        $sourceNode = $nodes[$edge[EdgeBuilderVisitor::EDGE_SOURCE]];
        $targetNode = $nodes[$edge[EdgeBuilderVisitor::EDGE_TARGET]];

        if (
          in_array($sourceNode[NodeBuilderVisitor::NODE_TYPE], $allowedNodeTypes)
          && in_array($targetNode[NodeBuilderVisitor::NODE_TYPE], $allowedNodeTypes)
        ) {
          $inheritanceEdges[] = $edge;
        }
      }
    }


    // catalog all source nodes
    foreach ($inheritanceEdges as $edge) {
      $sources[] = $edge[EdgeBuilderVisitor::EDGE_SOURCE];
      $targets[] = $edge[EdgeBuilderVisitor::EDGE_TARGET];
    }

    // Find nodes that hav no parent
    $leftovers = array_diff(array_unique(array_merge($targets, array_keys($nodes))), $sources);

    foreach ($leftovers as $leftover) {
      $graph[] = array(
        EdgeBuilderVisitor::EDGE_SOURCE  => $leftover,
        EdgeBuilderVisitor::EDGE_TARGET  => self::ROOT,
        EdgeBuilderVisitor::EDGE_TYPE    => EdgeBuilderVisitor::EDGE_TYPE_EXTENDS
      );
    }


    return array_merge($graph, $inheritanceEdges);
  }

}