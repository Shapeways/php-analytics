<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 5:55 PM
 */

namespace RoadRunnerAnalytics;


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
   * @var string[]
   */
  private $seenClassLikeNames = [];

  /**
   * NodeBuilder constructor.
   * @param ClassNameHelper $classNameHelper
   */
  public function __construct(ClassNameHelper $classNameHelper)
  {
    $this->classNameHelper = $classNameHelper;

    $this->addNode('external:Exception', 'Exception', NodeBuilder::NODE_TYPE_CLASS, array(self::NODE_EXTRA_EXTERNAl_ORIGIN => true));
    $this->addNode('external:ConsumerStrategies_SocketConsumer', 'ConsumerStrategies_SocketConsumer', NodeBuilder::NODE_TYPE_CLASS, array(self::NODE_EXTRA_EXTERNAl_ORIGIN => true));
    $this->addNode('external:PDO', 'PDO', NodeBuilder::NODE_TYPE_CLASS, array(self::NODE_EXTRA_EXTERNAl_ORIGIN => true));
    $this->addNode('external:PDOStatement', 'PDOStatement', NodeBuilder::NODE_TYPE_CLASS, array(self::NODE_EXTRA_EXTERNAl_ORIGIN => true));
    $this->addNode('external:Apache_Solr_Service', 'Apache_Solr_Service', NodeBuilder::NODE_TYPE_CLASS, array(self::NODE_EXTRA_EXTERNAl_ORIGIN => true));
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
   */
  private function addNode($nodeId, $nodeName, $nodeType, $extraData = array()) {

    $this->nodes[$nodeId] = array(
      NodeBuilder::NODE_ID => $nodeId,
      NodeBuilder::NODE_NAME => $nodeName,
      NodeBuilder::NODE_TYPE => $nodeType,
      NodeBuilder::NODE_EXTRA_DATA => $extraData
    );

  }

  /**
   * @param ClassLike $class_
   * @return string
   */
  private function getClassId(ClassLike $class_) {
    $nameStr = $this->classNameHelper->getQualifiedNameForClassLike($class_);

    return $this->filename . ':' . $nameStr;
  }

  /**
   * @param mixed $filename
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;
    $this->classNameHelper->resetCurrentUse();
  }

  private function enterClass(Class_ $node) {


  }

  private function enterInterface(Interface_ $node) {

  }

  private function enterClassLike(ClassLike $node) {
    $classId = $this->getClassId($node);
    $className = $this->classNameHelper->getQualifiedNameForClassLike($node);

    $nodeType = '';
    if ($node instanceof Class_) {
      $nodeType = NodeBuilder::NODE_TYPE_CLASS;
    }
    else if ($node instanceof Interface_) {
      $nodeType = NodeBuilder::NODE_TYPE_INTERFACE;
    }
    else if ($node instanceof Trait_) {
      $nodeType = NodeBuilder::NODE_TYPE_TRAIT;
    }

    $this->addNode($classId, $className, $nodeType);
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
}