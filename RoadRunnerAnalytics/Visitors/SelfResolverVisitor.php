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
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsNew;

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
        if (in_array($class->toString(), [ResolvedKeywordsNew::KEYWORD_SELF, ResolvedKeywordsNew::KEYWORD_STATIC])) {
          if ($this->currentClass) {
            return ResolvedKeywordsNew::fromNew_($node)
              ->setResolvedKeyword($class->toString())
              ->setResolvedClass($this->currentClass->namespacedName);
          }
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