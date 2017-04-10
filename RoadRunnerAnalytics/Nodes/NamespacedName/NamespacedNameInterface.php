<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 4:00 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;


use PhpParser\Node\Stmt\Interface_;

class NamespacedNameInterface extends Interface_ implements NamespacedNameNode
{
  use NamespacedNameDefaultImplementation;

  public static function fromInterface(Interface_ $node): NamespacedNameInterface {
    $interface = new NamespacedNameInterface($node->name, [], $node->attributes);

    $interface->extends = $node->extends;
    $interface->stmts = $node->stmts;

    return $interface;
  }
}