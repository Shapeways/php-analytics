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
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\Nodes\ResolvedKeywordsNew;

class EdgeBuilder extends NodeVisitorAbstract
{

  const EDGE_TYPE_NAMESPACE     = 'namespace';
  const EDGE_TYPE_SUBNAMESPACE  = 'subnamespace';
  const EDGE_TYPE_DEPENDENCY    = 'dependency';
  const EDGE_TYPE_INSTANTIATES  = 'instantiates';
  const EDGE_TYPE_SOURCE_FILE   = 'sourcefile';
  const EDGE_TYPE_EXTENDS       = 'extends';
  const EDGE_TYPE_TRAIT_USE     = 'traitUse';
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
  private $nodes = [];

  /**
   * @var array
   */
  private $edges = [];

  /**
   * @var string
   */
  private $filename;

  /**
   * @var ClassLike[]
   */
  private $currentClass = [];

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
      ->resetAll()
      ->setCurrentFilename($filename)
    ;
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

  }

  private function enterTraitUse(TraitUse $node) {
    $currentClassId = $this->classNameHelper->getClassId(end($this->currentClass));
    foreach ($node->traits as $trait) {
      $traitId = $this->classNameHelper->findClassId($trait, $this->nodes);
      $this->addEdge($currentClassId, $traitId, EdgeBuilder::EDGE_TYPE_TRAIT_USE);
    }

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
   * @param New_ $node
   */
  private function enterNew(New_ $node) {
    $class = $node->class;

    if ($node instanceof ResolvedKeywordsNew) {
      $currentClass = end($this->currentClass);
      if ($currentClass) {
        $resolvedName   = $node->getResolvedClass();

        $currentClassId = $this->classNameHelper->getClassId($currentClass);
        $targetClassId  = $this->classNameHelper->findClassId($resolvedName, $this->nodes);
        $this->addEdge($currentClassId, $targetClassId, EdgeBuilder::EDGE_TYPE_INSTANTIATES);
      }
    }
    else if ($class instanceof Name) {
      $currentClass = end($this->currentClass);
      if ($currentClass) {
        $currentClassId = $this->classNameHelper->getClassId($currentClass);
        $targetClassId = $this->classNameHelper->findClassId($class, $this->nodes);
        $this->addEdge($currentClassId, $targetClassId, EdgeBuilder::EDGE_TYPE_INSTANTIATES);
      }
      else {
        //echo $this->filename . ': Instantiation of class ' . $class->toString() . "\n";
      }
    }
    else if ($class instanceof Node\Expr\Variable) {
      echo $this->filename . ': New instance instantiation from variable: ' . $class->name;
    }
    else {
      echo $this->filename . ': New instance instantiation from unknown type ' . var_export($class, true);
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
    else if ($node instanceof TraitUse) {
      $this->enterTraitUse($node);
    }
    else if ($node instanceof New_) {
      $this->enterNew($node);
    }

  }

  /**
   * @param Node $node
   */
  public function leaveNode(Node $node) {
    if ($node instanceof ClassLike) {
      $this->leaveClassLike($node);
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