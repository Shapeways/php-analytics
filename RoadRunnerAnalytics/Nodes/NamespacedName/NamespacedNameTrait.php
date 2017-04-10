<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 4:04 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;


use PhpParser\Node\Stmt\Trait_;

class NamespacedNameTrait extends Trait_ implements NamespacedNameNode
{

  use NamespacedNameDefaultImplementation;

  public static function fromTrait(Trait_ $node): NamespacedNameTrait
  {
    $trait = new NamespacedNameTrait($node->name, [], $node->attributes);
    $trait->stmts = $node->stmts;

    return $trait;
  }
}