<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/17/17
 * Time: 5:52 PM
 */

namespace RoadRunnerAnalytics\GraphFormatters;


use RoadRunnerAnalytics\EdgeBuilder;
use RoadRunnerAnalytics\NodeBuilder;

class InheritanceHierarchyFormatter
{

  const ROOT = '\\';
  const SOURCE_DATA = 'sourceData';
  const TARGET_DATA = 'targetData';


  /**
   * @param NodeBuilder $nodeBuilder
   * @param EdgeBuilder $edgeBuilder
   * @return array
   */
  public function format(NodeBuilder $nodeBuilder, EdgeBuilder $edgeBuilder) {
    $graph = array(
      array(
        EdgeBuilder::EDGE_SOURCE => self::ROOT,
        EdgeBuilder::EDGE_TARGET => '',
        EdgeBuilder::EDGE_TYPE => EdgeBuilder::EDGE_TYPE_EXTENDS
      )
    );

    $nodes = $nodeBuilder->getNodes();
    $edges = $edgeBuilder->getEdges();

    $targets = array();
    $sources = array();
    $inheritanceEdges = array();

    $allowedNodeTypes = array(
      NodeBuilder::NODE_TYPE_INTERFACE,
      NodeBuilder::NODE_TYPE_CLASS,
      NodeBuilder::NODE_TYPE_TRAIT
    );

    // catalog all inheritance edges
    foreach ($edges as $edge) {
      if ($edge[EdgeBuilder::EDGE_TYPE] === EdgeBuilder::EDGE_TYPE_EXTENDS) {
        $sourceNode = $nodes[$edge[EdgeBuilder::EDGE_SOURCE]];
        $targetNode = $nodes[$edge[EdgeBuilder::EDGE_TARGET]];

        if (
          in_array($sourceNode[NodeBuilder::NODE_TYPE], $allowedNodeTypes)
          && in_array($targetNode[NodeBuilder::NODE_TYPE], $allowedNodeTypes)
        ) {
          $inheritanceEdges[] = array_merge($edge, array(
            self::SOURCE_DATA => $sourceNode,
            self::TARGET_DATA => $targetNode
          ));
        }
      }
    }


    // catalog all source nodes
    foreach ($inheritanceEdges as $edge) {
      $sources[] = $edge[EdgeBuilder::EDGE_SOURCE];
      $targets[] = $edge[EdgeBuilder::EDGE_TARGET];
    }

    // Find nodes that hav no parent
    $leftovers = array_diff(array_unique(array_merge($targets, array_keys($nodes))), $sources);

    foreach ($leftovers as $leftover) {

      $sourceNode = $nodes[$leftover];

      $graph[] = array(
        EdgeBuilder::EDGE_SOURCE  => $leftover,
        EdgeBuilder::EDGE_TARGET  => self::ROOT,
        EdgeBuilder::EDGE_TYPE    => EdgeBuilder::EDGE_TYPE_EXTENDS,
        self::SOURCE_DATA         => $sourceNode
      );
    }


    return array_merge($graph, $inheritanceEdges);
  }

}