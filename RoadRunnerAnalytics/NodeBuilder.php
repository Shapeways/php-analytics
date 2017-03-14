<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 5:55 PM
 */

namespace RoadRunnerAnalytics;


use PhpParser\NodeVisitorAbstract;

class NodeBuilder extends NodeVisitorAbstract
{

  const NODE_TYPE_FILE      = 'sourcefile';
  const NODE_TYPE           = 'type';
  const NODE_TYPE_CONSTANT  = 'constant';
  const NODE_TYPE_NAMESPACE = 'namespace';
  const NODE_EXTRA_DATA     = 'extraData';
  const NODE_TYPE_INTERFACE = 'interface';
  const NODE_TYPE_CLASS     = 'class';
  const NODE_ID             = 'id';
  const NODE_NAME           = 'name';
  const NODE_TYPE_UNDEFINED = 'unspecified';

  
  private $nodes = array();
}