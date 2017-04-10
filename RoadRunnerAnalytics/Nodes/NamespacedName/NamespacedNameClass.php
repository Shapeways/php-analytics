<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 3:39 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;


use PhpParser\Node\Stmt\Class_;

class NamespacedNameClass extends Class_ implements NamespacedNameNode
{
  use NamespacedNameDefaultImplementation;

  public static function fromClass(Class_ $node): NamespacedNameClass
  {
    $class = new NamespacedNameClass($node->name, [], $node->attributes);

    $class->flags = $node->flags;
    $class->type = $node->type;
    $class->name = $node->name;
    $class->extends = $node->extends;
    $class->implements = $node->implements;
    $class->stmts = $node->stmts;

    return $class;
  }

}