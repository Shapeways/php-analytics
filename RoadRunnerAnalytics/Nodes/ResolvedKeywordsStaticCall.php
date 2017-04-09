<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 10:19 AM
 */

namespace RoadRunnerAnalytics\Nodes;


use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;

class ResolvedKeywordsStaticCall extends StaticCall implements ResolvedKeywordsNode
{
  use ResolvedKeywordsDefaultImplementation;

  /**
   * @param StaticCall $node
   * @return ResolvedKeywordsStaticCall
   */
  public static function fromStaticCall(StaticCall $node): ResolvedKeywordsStaticCall {
    return new ResolvedKeywordsStaticCall($node->class, $node->name, $node->args, $node->attributes);
  }

}
