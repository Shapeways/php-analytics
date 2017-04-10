<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/26/17
 * Time: 8:27 AM
 */

namespace RoadRunnerAnalytics\Visitors;


use Monolog\Logger;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use Psr\Log\LoggerInterface;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsClassConstFetch;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsNew;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsNode;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsStaticCall;
use RoadRunnerAnalytics\Nodes\ResolvedKeywords\ResolvedKeywordsStaticPropertyFetch;

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

  /**
   * @var string
   */
  private $filename;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(ClassNameHelper $classNameHelper, LoggerInterface $logger, string $filename)
  {
    $this->classNameHelper = $classNameHelper;
    $this->filename = $filename;
    $this->logger = $logger;
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
   *
   * Get resolved string name
   *
   * @param Name $name
   * @return null|Name
   */
  private function getResolvedString(Name $name) {

    if (($this->isSelfOrStatic($name) || $this->isParent($name)) && empty($this->currentClass)) {
      return null;
    }

    if ($this->isSelfOrStatic($name) && !empty($this->currentClass)) {
      return $this->currentClass->namespacedName;
    }

    if ($this->isParent($name) && !empty($this->currentClass)) {
      if (($this->currentClass instanceof Class_) && ($this->currentClass->extends)) {
        return $this->currentClass->extends;
      }
    }

    return $name;
  }

  /**
   *
   * Returns true if name matches `parent` keyword
   *
   * @param Name $name
   * @return bool
   */
  private function isParent(Name $name) {
    return $name->toString() === ResolvedKeywordsNode::KEYWORD_PARENT;
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
        $resolvedName = $this->getResolvedString($class);
        if (!empty($resolvedName)) {
          return ResolvedKeywordsNew::fromNew_($node)
            ->setResolvedKeyword($class->toString())
            ->setResolvedClass($resolvedName);
        }
        else {
          $this->logger->warning($this->filename . ':' . $node->getLine() . ' ' . $class->toString() . ' instantiation outside of class context');
        }
      }
    }
    else if ($node instanceof StaticCall) {
      $class = $node->class;
      $resolvedName = $this->getResolvedString($class);
      if (!empty($resolvedName)) {
        return ResolvedKeywordsStaticCall::fromStaticCall($node)
          ->setResolvedKeyword($class->toString())
          ->setResolvedClass($resolvedName);
      }
      else {
        $this->logger->warning($this->filename . ':' . $node->getLine() . ' ' . $class->toString() . ' static call outside of class context');
      }
    }
    else if ($node instanceof StaticPropertyFetch) {
      $class = $node->class;
      $resolvedName = $this->getResolvedString($class);
      if (!empty($resolvedName)) {
        return ResolvedKeywordsStaticPropertyFetch::fromStaticPropertyFetch($node)
          ->setResolvedKeyword($class->toString())
          ->setResolvedClass($resolvedName);
      }
      else {
        $this->logger->warning($this->filename . ':' . $node->getLine() . ' ' . $class->toString() . ' static property fetch outside of class context');
      }
    }
    else if ($node instanceof ClassConstFetch) {
      $class = $node->class;
      if ($class instanceof Name) {
        $resolvedName = $this->getResolvedString($class);
        if (!empty($resolvedName)) {
          return ResolvedKeywordsClassConstFetch::fromClassConstFetch($node)
            ->setResolvedKeyword($class->toString())
            ->setResolvedClass($resolvedName);
        }
        else {
          $this->logger->warning($this->filename . ':' . $node->getLine() . ' ' . $class->toString() . ' reference outside of class context');
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