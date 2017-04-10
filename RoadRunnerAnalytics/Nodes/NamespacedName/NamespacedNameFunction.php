<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 4:06 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;


use PhpParser\Node\Stmt\Function_;

class NamespacedNameFunction extends Function_ implements NamespacedNameNode
{

  use NamespacedNameDefaultImplementation;

  public static function fromFunction(Function_ $node): NamespacedNameFunction
  {
    $function = new NamespacedNameFunction($node->name, [], $node->getAttributes());
    $function->byRef = $node->byRef;
    $function->params = $node->params;
    $function->returnType = $node->returnType;
    $function->stmts = $node->stmts;

    return $function;
  }
}