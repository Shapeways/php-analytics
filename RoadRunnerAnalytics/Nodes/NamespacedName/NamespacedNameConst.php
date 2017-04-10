<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 4:12 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;


use PhpParser\Node\Stmt\Const_;

class NamespacedNameConst extends Const_ implements NamespacedNameNode
{

  use NamespacedNameDefaultImplementation;

  public static function fromConst(Const_ $node): NamespacedNameConst
  {
    return new NamespacedNameConst($node->consts, $node->attributes);
  }
}
