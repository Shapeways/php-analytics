<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 5:55 PM
 */

namespace RoadRunnerAnalytics\Visitors;


use PhpParser\Node\Stmt\Class_;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;

class NodeBuilder extends NodeVisitorAbstract
{

  const NODE_TYPE_FILE      = 'sourcefile';
  const NODE_TYPE           = 'type';
  const NODE_TYPE_CONSTANT  = 'constant';
  const NODE_TYPE_NAMESPACE = 'namespace';
  const NODE_EXTRA_DATA     = 'extraData';
  const NODE_TYPE_INTERFACE = 'interface';
  const NODE_TYPE_TRAIT     = 'trait';
  const NODE_TYPE_CLASS     = 'class';
  const NODE_ID             = 'id';
  const NODE_NAME           = 'name';
  const NODE_TYPE_UNDEFINED = 'unspecified';

  const NODE_EXTRA_EXTERNAl_ORIGIN = 'externalOrigin';

  /**
   * @var array
   */
  private $nodes = array();

  /**
   * @var string
   */
  private $filename;

  /**
   * @var ClassNameHelper
   */
  private $classNameHelper;

  /**
   * @var string[][]
   */
  private $seenClassLikeNames = [
    NodeBuilder::NODE_TYPE_CLASS => [],
    NodeBuilder::NODE_TYPE_INTERFACE => [],
    NodeBuilder::NODE_TYPE_TRAIT => []
  ];

  /**
   * NodeBuilder constructor.
   * @param ClassNameHelper $classNameHelper
   */
  public function __construct(ClassNameHelper $classNameHelper)
  {
    $this->classNameHelper = $classNameHelper;
  }

  /**
   * @param Namespace_ $node
   */
  private function enterNamespace_(Namespace_ $node) {
    $this->classNameHelper->pushCurrentNamespace($node);
  }

  /**
   * @param Namespace_ $node
   */
  private function leaveNamespace_(Namespace_ $node) {
    $this->classNameHelper->popCurrentNamespace($node);
  }

  /**
   * @param $nodeId
   * @param $nodeName
   * @param $nodeType
   * @param array $extraData
   * @return mixed
   */
  private function addNode($nodeId, $nodeName, $nodeType, $extraData = array()): array {

    $this->nodes[$nodeId] = array(
      NodeBuilder::NODE_ID => $nodeId,
      NodeBuilder::NODE_NAME => $nodeName,
      NodeBuilder::NODE_TYPE => $nodeType,
      NodeBuilder::NODE_EXTRA_DATA => $extraData
    );

    return $this->nodes[$nodeId];
  }

  /**
   * @param mixed $filename
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;
    $this->classNameHelper
      ->resetAll()
      ->setCurrentFilename($filename)
    ;
  }

  /**
   * @param Class_ $node
   */
  private function enterClass(Class_ $node) {
    $classId = $this->classNameHelper->getClassId($node);
    $classname = $this->classNameHelper->getQualifiedNameForClassLike($node);
    $this->addNode($classId, $classname, NodeBuilder::NODE_TYPE_CLASS);

    if ($node->extends) {
      $this->seenClassLikeNames[NodeBuilder::NODE_TYPE_CLASS][] = $this->classNameHelper->getQualifiedName($node->extends);
    }

    foreach ($node->implements as $name) {
      $this->seenClassLikeNames[NodeBuilder::NODE_TYPE_INTERFACE][] = $this->classNameHelper->getQualifiedName($name);
    }
  }

  /**
   * @param Interface_ $node
   */
  private function enterInterface(Interface_ $node) {
    $classId = $this->classNameHelper->getClassId($node);
    $classname = $this->classNameHelper->getQualifiedNameForClassLike($node);
    $this->addNode($classId, $classname, NodeBuilder::NODE_TYPE_INTERFACE);

    foreach ($node->extends as $name) {
      $this->seenClassLikeNames[NodeBuilder::NODE_TYPE_INTERFACE][] = $this->classNameHelper->getQualifiedName($name);
    }
  }

  /**
   * @param Trait_ $node
   */
  private function enterTrait(Trait_ $node) {
    $classId = $this->classNameHelper->getClassId($node);
    $classname = $this->classNameHelper->getQualifiedNameForClassLike($node);
    $this->addNode($classId, $classname, NodeBuilder::NODE_TYPE_TRAIT);
  }

  private function enterClassLike(ClassLike $node) {

    if ($node instanceof Class_) {
      $this->enterClass($node);
    }
    else if ($node instanceof Interface_) {
      $this->enterInterface($node);
    }
    else if ($node instanceof Trait_) {
      $this->enterTrait($node);
    }

  }

  /**
   * @param Node $node
   */
  public function enterNode(Node $node)
  {
    parent::enterNode($node);

    if ($node instanceof ClassLike) {
      $this->enterClassLike($node);
    }
    else if ($node instanceof Namespace_) {
      $this->enterNamespace_($node);
    }
    else if ($node instanceof UseUse) {
      $this->classNameHelper->setCurrentUse($node);
    }

  }

  /**
   * @param Node $node
   */
  public function leaveNode(Node $node)
  {
    parent::leaveNode($node);

    if ($node instanceof Namespace_) {
      $this->leaveNamespace_($node);
    }
  }

  /**
   * @return array
   */
  public function getNodes() {
    return $this->nodes;
  }

  public function addExternalNodesForUnvisitedReferences() {

    $visitedNodeNames = array_map(function($node) {
      return $node[NodeBuilder::NODE_NAME];
    }, $this->nodes);

    foreach ($this->seenClassLikeNames as $typeString => $typeArray) {
      foreach ($typeArray as $name) {
        if (!in_array($name, $visitedNodeNames)) {
          $this->addNode('external:' . $name, $name, $typeString, array(self::NODE_EXTRA_EXTERNAl_ORIGIN => true));
        }
      }

    }


  }
}