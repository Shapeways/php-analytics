<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 12:23 PM
 */

namespace RoadRunnerAnalytics\Nodes;


use PhpParser\Node\Expr\ClassConstFetch;

class ResolvedKeywordsClassConstFetch extends ClassConstFetch implements ResolvedKeywordsNode
{
  use ResolvedKeywordsDefaultImplementation;

  public static function fromClassConstFetch(ClassConstFetch $classConstFetch): ResolvedKeywordsClassConstFetch {
    return new ResolvedKeywordsClassConstFetch($classConstFetch->class, $classConstFetch->name, $classConstFetch->attributes);
  }
}
