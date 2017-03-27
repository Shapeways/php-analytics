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
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsNew;

class SelfResolver extends NodeVisitorAbstract
{

  /**
   * @var ClassLike
   */
  private $currentClass;

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
        if ($class->toString() === ResolvedKeywordsNew::KEYWORD_SELF) {
          if ($this->currentClass) {
            return ResolvedKeywordsNew::fromNew_($node)
              ->setResolvedKeyword(ResolvedKeywordsNew::KEYWORD_SELF)
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