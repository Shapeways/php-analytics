<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 11:56 AM
 */

namespace RoadRunnerAnalytics\Nodes\ResolvedKeywords;


use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsDefaultImplementation;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsNode;

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
