<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/27/17
 * Time: 5:46 PM
 */


namespace RoadRunnerAnalytics\Nodes\ResolvedKeywords;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsDefaultImplementation;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsNode;

class ResolvedKeywordsNew extends New_ implements ResolvedKeywordsNode
{
  use ResolvedKeywordsDefaultImplementation;

  public static function fromNew_(New_ $node): ResolvedKeywordsNew {
    return new ResolvedKeywordsNew($node->class, $node->args, $node->attributes);
  }
}