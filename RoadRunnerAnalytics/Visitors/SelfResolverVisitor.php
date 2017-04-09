<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/26/17
 * Time: 8:27 AM
 */

namespace RoadRunnerAnalytics\Visitors;


use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsNew;
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsNode;
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsStaticCall;
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsStaticPropertyFetch;

class SelfResolverVisitor extends NodeVisitorAbstract
{

  /**
   * @var ClassLike
   */
  private $currentClass;

  /**
   * @var ClassNameHelper
   */
  private $classNameHelper;

  public function __construct(ClassNameHelper $classNameHelper)
  {
    $this->classNameHelper = $classNameHelper;
  }

  /**
   * Returns true if class name is `self` or `static`
   *
   * @param Name $name
   * @return bool
   */
  private function isSelfOrStatic(Name $name) {
    return in_array($name->toString(), [ResolvedKeywordsNode::KEYWORD_SELF, ResolvedKeywordsNode::KEYWORD_STATIC]);
  }

  /**
   * @param Node $node
   * @return Node
   */
  public function enterNode(Node $node): Node
  {

    if ($node instanceof ClassLike){
      $this->currentClass = $node;
    }
    else if ($node instanceof New_) {
      $class = $node->class;

      if ($class instanceof Name) {
        if ($this->isSelfOrStatic($class)) {
          if ($this->currentClass) {
            return ResolvedKeywordsNew::fromNew_($node)
              ->setResolvedKeyword($class->toString())
              ->setResolvedClass($this->currentClass->namespacedName);
          }
        }
      }
    }
    else if ($node instanceof StaticCall) {
      $class = $node->class;
      if ($this->isSelfOrStatic($class)) {
        if ($this->currentClass) {
          return ResolvedKeywordsStaticCall::fromStaticCall($node)
            ->setResolvedKeyword($class->toString())
            ->setResolvedClass($this->currentClass->namespacedName);
        }
      }
    }
    else if ($node instanceof StaticPropertyFetch) {
      $class = $node->class;
      if ($this->isSelfOrStatic($class)) {
        if ($this->currentClass) {
          return ResolvedKeywordsStaticPropertyFetch::fromStaticPropertyFetch($node)
            ->setResolvedKeyword($class->toString())
            ->setResolvedClass($this->currentClass->namespacedName);
        }
      }
    }

    return $node;
  }

  public function leaveNode(Node $node)
  {
    if ($node instanceof ClassLike){
      $this->currentClass = null;
    }
  }
}