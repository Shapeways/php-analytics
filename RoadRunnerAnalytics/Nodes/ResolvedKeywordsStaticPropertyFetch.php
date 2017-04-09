<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 11:56 AM
 */

namespace RoadRunnerAnalytics\Nodes;


use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;

class ResolvedKeywordsStaticPropertyFetch extends StaticPropertyFetch implements ResolvedKeywordsNode
{
  use ResolvedKeywordsDefaultImplementation;

  /**
   * @param StaticPropertyFetch $node
   * @return ResolvedKeywordsStaticPropertyFetch
   */
  public static function fromStaticPropertyFetch(StaticPropertyFetch $node): ResolvedKeywordsStaticPropertyFetch {
    return new ResolvedKeywordsStaticPropertyFetch($node->class, $node->name, $node->attributes);
  }

}
