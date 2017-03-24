<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/14/17
 * Time: 6:53 PM
 */

namespace RoadRunnerAnalytics;


use Exception;
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
      $this->classNameHelper->addIncludedFile($expr->value);
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
            $this->classNameHelper->addIncludedFile($subExpr->value);
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
   * @param Name $className
   * @return mixed|string
   */
  private function findClassId(Name $className, array $nodes) {

    $qualifiedName = $this->classNameHelper->getQualifiedName($className);
    $currentlyIncludedFiles = $this->classNameHelper->getCurrentIncludedFiles();

    $filteredNodes = array_filter($nodes, function($node) use($qualifiedName) {
      return $node[NodeBuilder::NODE_NAME] === $qualifiedName;
    });

    if (empty($filteredNodes)) {
      return 'external:' . $qualifiedName;
    }

    if (count($filteredNodes) === 1) {
      $firstNode = current($filteredNodes);
      return $firstNode[NodeBuilder::NODE_ID];
    }


    if (empty($currentlyIncludedFiles)) {
      var_dump("No possible disambiguation. Implicit dependency?", $qualifiedName);

      return 'implicit:' . $qualifiedName;
    }

    $finalMatch = array();

    foreach ($currentlyIncludedFiles as $partialFilename) {
      $finalMatch = array_filter($filteredNodes, function($node) use ($partialFilename) {

        return stristr($node[NodeBuilder::NODE_ID], $partialFilename);
      });
    }

    if (empty($finalMatch)) {
      var_dump("Unfound class", $qualifiedName);


      var_dump($filteredNodes);
      var_dump($currentlyIncludedFiles);

      return 'undefined:' . $qualifiedName;
    }

    $finalMatchSingle = current($finalMatch);

    return $finalMatchSingle[NodeBuilder::NODE_ID];
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
      $parentClassId = $this->findClassId($name, $this->nodes);
      $this->addEdge($interfaceId, $parentClassId, self::EDGE_TYPE_EXTENDS);
    }
  }

  /**
   * @param Class_ $node
   */
  private function enterClass_(Class_ $node) {
    $classId = $this->classNameHelper->getClassId($node);

    if ($node->extends) {

      $parentClassId = $this->findClassId($node->extends, $this->nodes);

      $this->addEdge($classId, $parentClassId, EdgeBuilder::EDGE_TYPE_EXTENDS);
    }

    foreach ($node->implements as $name) {
      $interfaceId = $this->findClassId($name, $this->nodes);

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
      $this->enterInclude_($node);
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