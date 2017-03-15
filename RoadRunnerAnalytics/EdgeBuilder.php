<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 6:53 PM
 */

namespace RoadRunnerAnalytics;


use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

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
   * @var string[]
   */
  private $currentIncludePartialFilename = array();

  /**
   * @var ClassLike[]
   */
  private $currentClass = array();

  /**
   * @var Namespace_[]
   */
  private $currentNamespace = array();

  /**
   * @var string[]
   */
  private $currentUse_ = array();

  /**
   * @var Namespace_
   */
  private $rootNamespace;

  /**
   * @var Class_
   */
  private $baseClass;

  /**
   * EdgeBuilder constructor.
   */
  public function __construct($nodes)
  {
    $this->nodes = $nodes;

    $this->rootNamespace = new Namespace_(
      new Name('')
    );

    $this->baseClass = new Class_(
      'BaseClass'
    );
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
   * @param ClassLike $node
   */
  private function pushCurrentClass(ClassLike $node) {
    array_push($this->currentClass, $node);
  }

  /**
   * @return ClassLike
   */
  private function popCurrentClass() {

    $poppedCurrentClass = array_pop($this->currentClass);

    if ($poppedCurrentClass === null) {
      echo "Unmatched class depth";
      exit(-1);
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

    if (!empty($this->currentIncludePartialFilename)) {
//      echo $this->filename . ":\n";
//      echo "\tIncluded files:\n";
//      var_dump($this->currentIncludePartialFilename);
//      echo "\n\n";
    }

    $this->filename                      = $filename;
    $this->currentNamespace              = array($this->rootNamespace);
    $this->currentUse_                   = array();
    $this->currentIncludePartialFilename = array();
  }

  private function addEdge($edgeSource, $edgeDestination, $edgeType, $edgeWeight = 1, $edgeLabel = '')
  {

    if (empty($this->nodes[$edgeSource])) {

//      throw new \Exception('Undefined edge source: ' . $edgeSource);
    }

    if (empty($this->nodes[$edgeDestination])) {

//      throw new \Exception('Undefined edge source: ' . $edgeDestination);
    }


    $edgeKey = "$edgeSource.$edgeDestination.$edgeType";

    $this->edges[$edgeKey] = array(
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

    $this->pushCurrentNamespace_($node);
  }

  /**
   * @param Namespace_ $node
   */
  private function leaveNamespace_(Namespace_ $node) {
    $this->popCurrentNamespace_();
  }

  private function enterInclude_(Include_ $node) {
    $expr = $node->expr;

    while ($expr instanceof BinaryOp) {
      $expr = $expr->right;
    }

//    echo $this->filename . ":\n";
    if ($expr instanceof ConstFetch) {
//      echo "\tconst " . $expr->name->toString() . "\n";
    } else if ($expr instanceof String_) {
//      echo "\tstring " . $expr->value . "\n";
      $this->currentIncludePartialFilename[] = $expr->value;
    } else if ($expr instanceof Variable) {
//      echo "\tvariable " . $expr->name . "\n";
    } else if ($expr instanceof PropertyFetch) {
      if ($expr->var instanceof Variable) {
//        echo "\tproperty fetch " . $expr->var->name . "::" . $expr->name . "\n";
      } else {
        var_dump($expr);
        echo "arrrrggg...."; die;
      }
    } else if ($expr instanceof ArrayDimFetch) {

//      echo "Array dimension fetch: ";
//      var_dump($expr->dim);
//      echo "\n";

    } else if ($expr instanceof ClassConstFetch) {

//      echo "Class constant fetch: ";
//      var_dump($expr);
//      echo "\n";

    } else if ($expr instanceof Node\Expr\FuncCall) {

      if ($expr->name instanceof Name) {
        if ($expr->name->toString() === 'realpath') {

          $subExpr = $expr->args[0];

          while ($subExpr instanceof BinaryOp) {
            $subExpr = $subExpr->right;
          }

          if ($subExpr instanceof String_) {
//            echo "\tstring " . $subExpr->value . "\n";
            $this->currentIncludePartialFilename[] = $subExpr->value;
          }

        } else {
//          echo "Func call: ";
//          var_dump($expr);
        }
      } else {
//        echo "Func call: ";
//        var_dump($expr);
      }

    } else if ($expr instanceof MethodCall) {
//      echo "Method call: ";
//      var_dump($expr);
      echo "\n";
    } else {
      var_dump($expr);

      echo "arrrgggg...."; die;
    }

  }

  /**
   * @param ClassLike $node
   * @return string
   */
  private function getQualifiedNameForClassLike(ClassLike $node) {
    $nameStr = $node->name;

    if ($this->currentUse_[$nameStr]) {
      return $this->currentUse_[$nameStr];
    }

    $nameStr = $this->peekCurrentNamespace_() . '\\' . $nameStr;

    return ltrim($nameStr, '\\');
  }

  /**
   * @param Class_ $class_
   * @return string
   */
  private function getClassId(Class_ $class_) {
    $nameStr = $this->getQualifiedNameForClassLike($class_);

    return $this->filename . ':' . $nameStr;
  }

  /**
   * @param Name $name
   * @return string
   */
  private function getQualifiedName(Name $name) {
    $nameStr = $name->toString();
    if ($name->isUnqualified()) {

      if (!empty($this->currentUse_[$nameStr])) {
        return $this->currentUse_[$nameStr];
      }

      return $this->peekCurrentNamespace_() . '\\' . $nameStr;
    }
    else if ($name->isFullyQualified()) {
      return ltrim($nameStr, '\\');
    }

    return $nameStr;
  }

  /**
   * @param Name $className
   * @return mixed|string
   */
  private function findClassId(Name $className) {

    $qualifiedName = ltrim($this->getQualifiedName($className), '\\');

    $filteredNodes = array_filter($this->nodes, function($node) use($qualifiedName) {
      return $node[NodeBuilder::NODE_NAME] === $qualifiedName;
    });

    $alternateFilteredNodes = array_filter($this->nodes, function($node) use($name) {
      return stristr($node[NodeBuilder::NODE_ID], $name);
    });

    if (empty($filteredNodes)) {

//      var_dump($this->filename);
//
//      var_dump("Undefined class: " . $qualifiedName);
//      var_dump("Alternates", $filteredNodes);
//      var_dump("Alternates", $alternateFilteredNodes);

      //ConsumerStrategies_SocketConsumer
      //PDOStatement
      if (
        ($qualifiedName !== 'Exception') ||
        ($qualifiedName !== 'ConsumerStrategies_SocketConsumer') ||
        ($qualifiedName !== 'PDO') ||
        ($qualifiedName !== 'PDOStatement') ||
        ($qualifiedName !== 'Apache_Solr_Service')
      ) {
//        die;
      }

      return 'external:' . $qualifiedName;
    }

    if (count($filteredNodes) === 1) {
      $firstNode = current($filteredNodes);
      return $firstNode[NodeBuilder::NODE_ID];
    }


    if (empty($this->currentIncludePartialFilename)) {
//      var_dump("No possible disambiguation. Implicit dependency?", $qualifiedName);

      return 'implicit:' . $qualifiedName;
    }

    $finalMatch = array();

    foreach ($this->currentIncludePartialFilename as $partialFilename) {
      $finalMatch = array_filter($filteredNodes, function($node) use ($partialFilename) {

        return stristr($node[NodeBuilder::NODE_ID], $partialFilename);
      });
    }

    if (empty($finalMatch)) {
      var_dump("Unfound class", $qualifiedName);


      var_dump($filteredNodes);
      var_dump($this->currentIncludePartialFilename);

      return 'undefined:' . $qualifiedName;
    }

    $finalMatchSingle = current($finalMatch);


    if ($className->toString() === 'CheckingToolController') {
      var_dump($finalMatchSingle[NodeBuilder::NODE_ID]);
      echo "\n\n";
    }

    return $finalMatchSingle[NodeBuilder::NODE_ID];
  }

  /**
   * @param Class_ $node
   */
  private function enterClass_(Class_ $node) {

    $this->pushCurrentClass($node);

    $classId = $this->getClassId($node);
    $className = $this->getQualifiedNameForClassLike($node);

    if ($node->extends) {

      $parentClassId = $this->findClassId($node->extends);

      if (stristr($classId, 'CheckingContentReviewController') || stristr($parentClassId, 'CheckingContentReviewController')) {
        var_dump($classId);
        var_dump($className);

        var_dump($parentClassId);
        var_dump($node->extends->toString());

        echo "\n\n";
      }

      $this->addEdge($classId, $parentClassId, EdgeBuilder::EDGE_TYPE_EXTENDS);
    } else {
      $parentClassId = $this->baseClass->name;

      $this->addEdge($classId, $parentClassId, EdgeBuilder::EDGE_TYPE_EXTENDS);
    }

    foreach ($node->implements as $name) {
//      $this->addEdge($classId, $this->getQualifiedName($name), self::EDGE_TYPE_IMPLEMENTS);
    }
  }

  private function leaveClass(Class_ $node) {

    $this->popCurrentClass();

  }

  /**
   * @param Node $node
   */
  public function enterNode(Node $node) {
    if ($node instanceof Include_) {
      $this->enterInclude_($node);
    }
    else if ($node instanceof Class_) {
      $this->enterClass_($node);
    }
    else if ($node instanceof Namespace_) {
      $this->enterNamespace_($node);
    }
    else if ($node instanceof UseUse) {
      $this->currentUse_[$node->alias] = $node->name->toString();
    }

  }

  /**
   * @param Node $node
   */
  public function leaveNode(Node $node) {
    if ($node instanceof Class_) {
      $this->leaveClass($node);
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