<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 6:53 PM
 */

namespace RoadRunnerAnalytics;


class EdgeBuilder
{

  const EDGE_TYPE_NAMESPACE     = 'namespace';
  const EDGE_TYPE_SUBNAMESPACE  = 'subnamespace';
  const EDGE_TYPE_DEPENDENCY    = 'dependency';
  const EDGE_TYPE_CREATES       = 'creates';
  const EDGE_TYPE_SOURCE_FILE   = 'sourcefile';
  const EDGE_TYPE_EXTENDS       = 'extends';
  const EDGE_TYPE_IMPLEMENTS    = 'implements';
  const EDGE_LABEL              = 'label';
  const EDGE_TYPE_CONSUMER      = 'consumer';
  const EDGE_SOURCE             = 'source';
  const EDGE_TYPE_STATIC_ACCESS = 'staticAccess';
  const EDGE_WEIGHT             = 'weight';
  const EDGE_TARGET             = 'target';
  const EDGE_TYPE               = 'type';
  
}