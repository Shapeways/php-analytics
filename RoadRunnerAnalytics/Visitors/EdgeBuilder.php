<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 6:53 PM
 */

namespace RoadRunnerAnalytics\Visitors;


use Exception;
use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;

class EdgeBuilder extends NodeVisitorAbstract
{

  const EDGE_TYPE_NAMESPACE     = 'namespace';
  const EDGE_TYPE_SUBNAMESPACE  = 'subnamespace';
  const EDGE_TYPE_DEPENDENCY    = 'dependency';
  const EDGE_TYPE_CREATES       = 'creates';
  const EDGE_TYPE_SOURCE_FILE   = 'sourcefile';
  const EDGE_TYPE_EXTENDS       = 'extends';
  const EDGE_TYPE_IMPLEMENTS    = 'implements';
  const EDGE_LABEL              = 'label';
  const EDGE_ID                 = 'id';
  const EDGE_TYPE_CONSUMER      = 'consumer';
  const EDGE_SOURCE             = 'source';
  const EDGE_TYPE_STATIC_ACCESS = 'staticAccess';
  const EDGE_WEIGHT             = 'weight';
  const EDGE_TARGET             = 'target';
  const EDGE_TYPE               = 'type';

  /**
   * @var array
   */
  private $nodes = array();

  /**
   * @var array
   */
  private $edges = array();

  /**
   * @var string
   */
  private $filename;

  /**
   * @var ClassLike[]
   */
  private $currentClass = array();

  /**
   * @var ClassNameHelper
   */
  private $classNameHelper;

  /**
   * EdgeBuilder constructor.
   */
  public function __construct(array $nodes, ClassNameHelper $classNameHelper)
  {
    $this->nodes = $nodes;
    $this->classNameHelper = $classNameHelper;
  }


  /**
   * @param ClassLike $node
   */
  private function pushCurrentClass(ClassLike $node) {
    array_push($this->currentClass, $node);
  }

  /**
   * @return mixed|ClassLike
   * @throws Exception
   */
  private function popCurrentClass() {

    $poppedCurrentClass = array_pop($this->currentClass);

    if ($poppedCurrentClass === null) {
      throw new Exception("Unmatched class depth");
    }

    return $poppedCurrentClass;
  }

  /**
   * @return mixed|ClassLike
   */
  private function peekCurrentClass()
  {
    return end($this->currentClass);
  }

  /**
   * @param mixed $filename
   */
  public function setFilename($filename)
  {

    $this->filename = $filename;

    $this->classNameHelper
      ->setCurrentFilename($filename)
      ->resetCurrentNamespace()
      ->resetCurrentUse()
      ->resetIncludedFiles();
  }

  private function addEdge($edgeSource, $edgeDestination, $edgeType, $edgeWeight = 1, $edgeLabel = '')
  {

    if (empty($this->nodes[$edgeSource])) {

//      throw new \Exception('Undefined edge source: ' . $edgeSource);
    }

    if (empty($this->nodes[$edgeDestination])) {

//      throw new \Exception('Undefined edge source: ' . $edgeDestination);
    }


    $edgeId = "$edgeSource.$edgeDestination.$edgeType";

    $this->edges[$edgeId] = array(
      EdgeBuilder::EDGE_ID => $edgeId,
      EdgeBuilder::EDGE_SOURCE => $edgeSource,
      EdgeBuilder::EDGE_TARGET => $edgeDestination,
      EdgeBuilder::EDGE_TYPE => $edgeType,
      EdgeBuilder::EDGE_WEIGHT => $edgeWeight,
      EdgeBuilder::EDGE_LABEL => $edgeLabel
    );
  }

  /**
   * @param Namespace_ $node
   */
  private function enterNamespace_(Namespace_ $node)
  {
    $this->classNameHelper->pushCurrentNamespace($node);
  }

  /**
   * @param Namespace_ $node
   */
  private function leaveNamespace_(Namespace_ $node) {
    $this->classNameHelper->popCurrentNamespace($node);
  }

  /**
   * @param ClassLike $node
   */
  private function enterClassLike(ClassLike $node) {
    $this->pushCurrentClass($node);

    if ($node instanceof Interface_) {
      $this->enterInterface($node);
    }
    else if ($node instanceof Class_) {
      $this->enterClass_($node);
    }
    else if ($node instanceof Trait_) {
      $this->enterTrait($node);
    }
  }

  /**
   * @param Trait_ $node
   */
  private function enterTrait(Trait_ $node) {
    var_dump($node->name);
  }

  /**
   * @param Interface_ $node
   */
  private function enterInterface(Interface_ $node) {
    $interfaceId = $this->classNameHelper->getClassId($node);

    foreach ($node->extends as $name) {
      $parentClassId = $this->classNameHelper->findClassId($name, $this->nodes);
      $this->addEdge($interfaceId, $parentClassId, self::EDGE_TYPE_EXTENDS);
    }
  }

  /**
   * @param Class_ $node
   */
  private function enterClass_(Class_ $node) {
    $classId = $this->classNameHelper->getClassId($node);

    if ($node->extends) {

      $parentClassId = $this->classNameHelper->findClassId($node->extends, $this->nodes);

      $this->addEdge($classId, $parentClassId, EdgeBuilder::EDGE_TYPE_EXTENDS);
    }

    foreach ($node->implements as $name) {
      $interfaceId = $this->classNameHelper->findClassId($name, $this->nodes);

      $this->addEdge($classId, $interfaceId, self::EDGE_TYPE_IMPLEMENTS);
    }
  }

  /**
   * @param ClassLike $node
   * @throws Exception
   */
  private function leaveClassLike(ClassLike $node) {

    try {
      $this->popCurrentClass();
    }
    catch (Exception $e) {
      var_dump($e->getMessage());
      var_dump($node);

      throw $e;
    }

  }

  /**
   * @param Node $node
   */
  public function enterNode(Node $node) {
    if ($node instanceof Include_) {
      $this->classNameHelper->enterInclude($node);
    }
    else if ($node instanceof ClassLike) {
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
  public function leaveNode(Node $node) {
    if ($node instanceof ClassLike) {
      $this->leaveClassLike($node);
    }
    else if ($node instanceof Namespace_) {
      $this->leaveNamespace_($node);
    }
  }

  /**
   * @return array
   */
  public function getEdges()
  {
    return $this->edges;
  }

}