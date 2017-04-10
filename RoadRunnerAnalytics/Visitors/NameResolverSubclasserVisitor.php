<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 1:19 PM
 */

namespace RoadRunnerAnalytics\Visitors;


use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitor\NameResolver;
use Psr\Log\LoggerInterface;
use RoadRunnerAnalytics\Nodes\NamespacedName\NamespacedNameClass;
use RoadRunnerAnalytics\Nodes\NamespacedName\NamespacedNameConst;
use RoadRunnerAnalytics\Nodes\NamespacedName\NamespacedNameFunction;
use RoadRunnerAnalytics\Nodes\NamespacedName\NamespacedNameInterface;
use RoadRunnerAnalytics\Nodes\NamespacedName\NamespacedNameTrait;

class NameResolverSubclasserVisitor extends NameResolver
{

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @param LoggerInterface $logger
   */
  public function setLogger(LoggerInterface $logger)
  {

    $this->logger = $logger;

    return $this;
  }

  /**
   *
   * Do everything that the namspace resolver does. Wrap the results in
   * a node that has a property type for `->namespacedName`
   *
   * @param Node $node
   * @return null|Node|void
   */
  public function enterNode(Node $node)
  {
    $returnValue = parent::enterNode($node);

    if (!empty($node->namespacedName)) {

      if ($node instanceof Class_) {
        return NamespacedNameClass::fromClass($node)->setNamespacedName($node->namespacedName);
      }

      if ($node instanceof Interface_) {
        return NamespacedNameInterface::fromInterface($node)->setNamespacedName($node->namespacedName);
      }

      if ($node instanceof Trait_) {
        return NamespacedNameTrait::fromTrait($node)->setNamespacedName($node->namespacedName);
      }

      if ($node instanceof Function_) {
        return NamespacedNameFunction::fromFunction($node)->setNamespacedName($node->namespacedName);
      }

      if ($node instanceof Const_) {
        return NamespacedNameConst::fromConst($node)->setNamespacedName($node->namespacedName);
      }

    }

    return $returnValue;
  }


}