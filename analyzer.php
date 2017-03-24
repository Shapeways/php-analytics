<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;


use RoadRunnerAnalytics\EdgeBuilder;
use RoadRunnerAnalytics\GraphFormatters\InheritanceHierarchyFormatter;
use RoadRunnerAnalytics\Helpers\ClassNameHelper;
use RoadRunnerAnalytics\NodeBuilder;

class MapBuilder extends NodeVisitorAbstract {


  private $nodes = array();
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
   * @var Use_[]
   */
  private $currentUse_ = array();

  public function __construct()
  {
    // Root namespace
//    $this->addNode('\\', '\\', self::EDGE_TYPE_NAMESPACE);
  }

  private function addNode($nodeId, $nodeName, $nodeType, $extraData = array()) {

    $this->nodes[$nodeId] = array(
      NodeBuilder::NODE_ID => $nodeId,
      NodeBuilder::NODE_NAME => $nodeName,
      NodeBuilder::NODE_TYPE => $nodeType
    );

  }

  private function addEdge($edgeSource, $edgeDestination, $edgeType, $edgeWeight = 1, $edgeLabel = '') {
    $edgeKey = "$edgeSource.$edgeDestination.$edgeType";

    $this->edges[$edgeKey] = array(
      EdgeBuilder::EDGE_SOURCE => $edgeSource,
      EdgeBuilder::EDGE_TARGET => $edgeDestination,
      EdgeBuilder::EDGE_TYPE => $edgeType,
      EdgeBuilder::EDGE_WEIGHT => $edgeWeight,
      EdgeBuilder::EDGE_LABEL => $edgeLabel
    );

    if (empty($this->nodes[$edgeSource])) {

      $this->addNode($edgeSource, $edgeSource, NodeBuilder::NODE_TYPE_UNDEFINED);
    }

    if (empty($this->nodes[$edgeDestination])) {

      $this->addNode($edgeDestination, $edgeDestination, NodeBuilder::NODE_TYPE_UNDEFINED);
    }
  }

  /**
   * @param mixed $filename
   */
  public function setFilename($filename)
  {

    if (!empty($this->currentIncludePartialFilename)) {
      echo $this->filename . ":\n";
      echo "\tIncluded files:\n";
      var_dump($this->currentIncludePartialFilename);
      echo "\n\n";
    }

    $this->filename                      = $filename;
    $this->currentUse_                   = array();
    $this->currentIncludePartialFilename = array();

    $this->addNode($filename, basename($filename), NodeBuilder::NODE_TYPE_FILE);
  }

  /**
   * @return array
   */
  public function getNodes()
  {

    return $this->nodes;
  }

  /**
   * @return array
   */
  public function getEdges()
  {

    return $this->edges;
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
  private function peekCurrentClass() {
    return end($this->currentClass);
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
   * @return mixed|Namespace_
   */
  private function peekCurrentNamespace_() {
    $peekedNamespace = end($this->currentNamespace);
    return $peekedNamespace? $peekedNamespace->name->toString() . '\\' : '';
  }

  private function getQualifiedName(Name $name) {
    $nameStr = $name->toString();
    if ($name->isUnqualified()) {

      if (!empty($this->currentUse_[$nameStr])) {
        return $this->currentUse_[$nameStr];
      }

      return $this->peekCurrentNamespace_() . $nameStr;
    }
    else if ($name->isFullyQualified()) {
      return ltrim($nameStr, '\\');
    }

    return $nameStr;
  }

  private function getQualifiedNameForClassLike(ClassLike $node) {
    $nameStr = $node->name;

    if ($this->currentUse_[$nameStr]) {
      return $this->currentUse_[$nameStr];
    }

    $nameStr = $this->peekCurrentNamespace_() . $nameStr;
    $nameStr = ltrim($nameStr, '\\');

    return $nameStr;
  }

  private function getClassId(ClassLike $node) {
    $className = $this->getQualifiedNameForClassLike($node);

    return $this->filename . ':' . $className;
  }

  private function enterNew_(New_ $node){
    $currentClass = $this->peekCurrentClass();

    if ($currentClass) {
      $classStr = $this->getQualifiedNameForClassLike($currentClass);

      if ($node->class instanceof Name) {
        $newClass = $this->getQualifiedName($node->class);
//        $this->addEdge($classStr, $newClass, self::EDGE_TYPE_CREATES);
      }
    }
  }

  private function enterInterface_(Interface_ $node) {

    $interfaceId = $this->getQualifiedNameForClassLike($node);
    $interfaceName = $interfaceId;

//    $this->addNode($interfaceId, $interfaceName, self::NODE_TYPE_INTERFACE);
//    $this->addEdge($interfaceId, $this->filename, self::EDGE_TYPE_SOURCE_FILE);

    foreach ($node->extends as $name) {
//      $this->addEdge($interfaceId, $this->getQualifiedName($name), self::EDGE_TYPE_EXTENDS);
    }
  }

  private function findClassNode(Name $parentClass, $potentialFileMatches = array()) {


    echo "findClassNode"; die;
  }

  private function enterClass_(Class_ $node) {

    $classId = $this->getClassId($node);
    //$this->getClassId($node);
    $className = $this->getQualifiedNameForClassLike($node);

    $this->addNode($classId, $className, NodeBuilder::NODE_TYPE_CLASS);
//    $this->addEdge($classId, $this->filename, self::EDGE_TYPE_SOURCE_FILE);

    if ($node->extends) {
      $this->addEdge($classId, $this->getQualifiedName($node->extends), EdgeBuilder::EDGE_TYPE_EXTENDS);
    }

    foreach ($node->implements as $name) {
//      $this->addEdge($classId, $this->getQualifiedName($name), self::EDGE_TYPE_IMPLEMENTS);
    }
  }

  private function enterClassLike(ClassLike $node) {

    $this->pushCurrentClass($node);

    if ($node instanceof Interface_) {
      $this->enterInterface_($node);
    } else if ($node instanceof Class_) {
      $this->enterClass_($node);
    }


    $currentNameSpace = $this->peekCurrentNamespace_();
    if (empty($currentNameSpace)) {
      $currentNameSpace = '\\';
    }

//    $this->addEdge($this->getQualifiedNameForClassLike($node), $currentNameSpace, self::EDGE_TYPE_NAMESPACE);
  }

  private function leaveClassLike(ClassLike $node) {

    $this->popCurrentClass();

  }

  private function enterNamespace_(Namespace_ $node) {
    $this->pushCurrentNamespace_($node);

//    $this->addNode($node->name->toString() . '\\', $node->name->toString() . '\\', self::NODE_TYPE_NAMESPACE);
//    $this->addEdge($node->name->toString() . '\\', $this->filename, self::EDGE_TYPE_SOURCE_FILE);
//    $this->addEdge($node->name->toString() . '\\', '\\', self::EDGE_TYPE_SUBNAMESPACE);
  }

  private function leaveNamespace_(Namespace_ $node) {
    $this->popCurrentNamespace_();
  }

  private function enterInclude_(Include_ $node) {
    $expr = $node->expr;

    while ($expr instanceof BinaryOp) {
      $expr = $expr->right;
    }

    echo $this->filename . ":\n";
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

  public function enterNode(Node $node)
  {
    parent::enterNode($node);

    if ($node instanceof Include_) {
      $this->enterInclude_($node);
    }
    else if ($node instanceof ClassLike) {
      $this->enterClassLike($node);
    }
    else if ($node instanceof Namespace_) {
      $this->enterNamespace_($node);
    }
    else if ($node instanceof New_) {
      $this->enterNew_($node);
    }
    else if ($node instanceof UseUse) {
      $this->currentUse_[$node->alias] = $node->name->toString();
    }

  }

  public function leaveNode(Node $node)
  {
    parent::leaveNode($node);

    if ($node instanceof ClassLike) {
      $this->leaveClassLike($node);
    }
    else if ($node instanceof Namespace_) {
      $this->leaveNamespace_($node);
    }
  }

  public function __destruct()
  {

  }

}

$codeEdges = array();
$codeNodes = array();

$nodeBuilder = new NodeBuilder(new ClassNameHelper());

$filesToAnalyze = array();

echo "Building file list...\n";
while ($f = fgets(STDIN)) {

  $filename     = str_replace(PHP_EOL, '', $f);
  $filePath     = dirname(__FILE__) . '/' . $filename;
  $absolutePath = realpath($filePath);

  $filesToAnalyze[] = $absolutePath;
}


echo "Analyzing nodes...\n";
foreach ($filesToAnalyze as $absolutePath) {
  $code = file_get_contents($absolutePath);

  $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

  try {
    $stmts = $parser->parse($code);

    $traverser = new NodeTraverser();
    $nodeBuilder->setFilename($absolutePath);
    $traverser->addVisitor(new NameResolver());
    $traverser->addVisitor($nodeBuilder);
    $stmts = $traverser->traverse($stmts);

  } catch (Exception $e) {
    echo basename($absolutePath) . ":\n";
    echo "\tParse Error: ", $e->getMessage(), "\n";
  }
}

echo "Analyzing edges...\n";
$edgeBuilder = new EdgeBuilder($nodeBuilder->getNodes(), new ClassNameHelper());
foreach ($filesToAnalyze as $absolutePath) {
  $code = file_get_contents($absolutePath);

  $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

  try {
    $stmts = $parser->parse($code);

    $traverser = new NodeTraverser();
    $edgeBuilder->setFilename($absolutePath);
    $traverser->addVisitor(new NameResolver());
    $traverser->addVisitor($edgeBuilder);
    $stmts = $traverser->traverse($stmts);

  } catch (Exception $e) {
    echo basename($absolutePath) . ":\n";
    echo "\tParse Error: ", $e->getMessage(), "\n";
  }
}


$js = 'var roadRunnerDeps = ';
$js .= json_encode(array(
                     'edges' => $edgeBuilder->getEdges(),
                     'nodes' => $nodeBuilder->getNodes(),
                     'classInheritanceEdges' => (new InheritanceHierarchyFormatter())->format($nodeBuilder, $edgeBuilder)
                   ));

$jsFilename = dirname(__FILE__) . '/www/js/class-graph.js';
file_put_contents($jsFilename, $js);

