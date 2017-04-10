<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 12:23 PM
 */

namespace RoadRunnerAnalytics\Nodes\ResolvedKeywords;


use PhpParser\Node\Expr\ClassConstFetch;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsNode;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsDefaultImplementation;

class ResolvedKeywordsClassConstFetch extends ClassConstFetch implements ResolvedKeywordsNode
{
  use ResolvedKeywordsDefaultImplementation;

  public static function fromClassConstFetch(ClassConstFetch $classConstFetch): ResolvedKeywordsClassConstFetch {
    return new ResolvedKeywordsClassConstFetch($classConstFetch->class, $classConstFetch->name, $classConstFetch->attributes);
  }
}
