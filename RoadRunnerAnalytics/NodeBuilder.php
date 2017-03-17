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
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

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

  /**
   * @var array
   */
  private $nodes = array();

  /**
   * @var Namespace_[]
   */
  private $currentNamespace = array();

  /**
   * @var string
   */
  private $filename;

  /**
   * @var Namespace_
   */
  private $rootNamespace;

  public function __construct()
  {
    $this->rootNamespace = new Namespace_(
      new Name('')
    );

    $this->addNode('external:Exception', 'Exception', NodeBuilder::NODE_TYPE_CLASS);
    $this->addNode('external:ConsumerStrategies_SocketConsumer', 'ConsumerStrategies_SocketConsumer', NodeBuilder::NODE_TYPE_CLASS);
    $this->addNode('external:PDO', 'PDO', NodeBuilder::NODE_TYPE_CLASS);
    $this->addNode('external:PDOStatement', 'PDOStatement', NodeBuilder::NODE_TYPE_CLASS);
    $this->addNode('external:Apache_Solr_Service', 'Apache_Solr_Service', NodeBuilder::NODE_TYPE_CLASS);
  }

  /**
   * @param Namespace_ $node
   */
  private function pushCurrentNamespace_(Namespace_ $node) {
    array_push($this->currentNamespace, $node);
  }

  /**
   * @return Namespace_
   */
  private function popCurrentNamespace_() {

    $poppedCurrentNamespace = array_pop($this->currentNamespace);

    if ($poppedCurrentNamespace === null) {
      echo "Unmatched class depth";
      exit(-1);
    }

    return $poppedCurrentNamespace;
  }

  /**
   * @return string
   */
  private function peekCurrentNamespace_() {
    $peekedNamespace = end($this->currentNamespace);
    return $peekedNamespace? $peekedNamespace->name->toString() : '';
  }

  /**
   * @param Namespace_ $node
   */
  private function enterNamespace_(Namespace_ $node) {
    $this->pushCurrentNamespace_($node);

//    $this->addNode($node->name->toString(), $node->name->toString(), self::NODE_TYPE_NAMESPACE);
  }

  /**
   * @param Namespace_ $node
   */
  private function leaveNamespace_(Namespace_ $node) {
    $this->popCurrentNamespace_();
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
      NodeBuilder::NODE_TYPE => $nodeType
    );

  }

  /**
   * @param ClassLike $node
   * @return string
   */
  private function getQualifiedNameForClass(ClassLike $node) {
    $nameStr = $node->name;

    $nameStr = $this->peekCurrentNamespace_() . '\\' . $nameStr;

    return ltrim($nameStr, '\\');
  }

  /**
   * @param Class_ $class_
   * @return string
   */
  private function getClassId(ClassLike $class_) {
    $nameStr = $this->getQualifiedNameForClass($class_);

    return $this->filename . ':' . $nameStr;
  }

  /**
   * @param mixed $filename
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;
    $this->currentNamespace = array($this->rootNamespace);

//    $this->addNode($filename, basename($filename), NodeBuilder::NODE_TYPE_FILE);
  }

  private function enterClass(Class_ $node) {

    $classId = $this->getClassId($node);
    $className = ltrim($this->getQualifiedNameForClass($node), '\\');

    $this->addNode($classId, $className, NodeBuilder::NODE_TYPE_CLASS);
  }

  private function enterInterface(Interface_ $node) {
    $classId = $this->getClassId($node);
    $className = ltrim($this->getQualifiedNameForClass($node), '\\');
    $this->addNode($classId, $className, NodeBuilder::NODE_TYPE_INTERFACE);
  }

  private function enterClassLike(ClassLike $node) {
    $classId = $this->getClassId($node);
    $className = ltrim($this->getQualifiedNameForClass($node), '\\');

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