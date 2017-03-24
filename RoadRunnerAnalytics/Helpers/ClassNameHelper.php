<?php


/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/24/17
 * Time: 11:29 AM
 */


namespace RoadRunnerAnalytics\Helpers;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use RoadRunnerAnalytics\Visitors\NodeBuilder;

/**
 * Class ClassNameHelper
 *
 * Facilitates class name resolution for the current file.
 *
 * @package RoadRunnerAnalytics\Helpers
 */
class ClassNameHelper
{
  /**
   * @var string[]
   */
  private $currentIncludedFiles = [];

  /**
   * @var string
   */
  private $currentFilename;

  /**
   * ClassNameHelper constructor.
   */
  public function __construct()
  {
  }

  /**
   * @return ClassNameHelper
   */
  public function resetAll(): ClassNameHelper {
    return $this
      ->resetIncludedFiles();
  }

  /**
   * @param ClassLike $node
   * @return string
   */
  public function getQualifiedNameForClassLike(ClassLike $node): string {
    if (!empty($node->namespacedName)) {
      return $node->namespacedName->toString();
    }

    return $node->name;
  }

  /**
   * @param Name $name
   * @return string
   */
  public function getQualifiedName(Name $name): string {

    return $name->toString();
  }

  /**
   * @return $this
   */
  public function resetIncludedFiles(): ClassNameHelper {
    $this->currentIncludedFiles = [];

    return $this;
  }

  /**
   * @param string $partialFilename
   * @return $this
   */
  public function addIncludedFile(string $partialFilename): ClassNameHelper {
    $this->currentIncludedFiles[] = $partialFilename;

    return $this;
  }

  /**
   * @return string[]
   */
  public function getCurrentIncludedFiles(): array
  {
    return $this->currentIncludedFiles;
  }

  /**
   * @return string
   */
  public function getCurrentFilename(): string
  {

    return $this->currentFilename;
  }

  /**
   * @param string $currentFilename
   * @return ClassNameHelper
   */
  public function setCurrentFilename(string $currentFilename): ClassNameHelper
  {

    $this->currentFilename = $currentFilename;

    return $this;
  }

  /**
   * @param ClassLike $class_
   * @return string
   */
  public function getClassId(ClassLike $class_): string {
    if (!empty($class_->filenameId)) {
      return $class_->filenameId;
    }

    $nameStr = $this->getQualifiedNameForClassLike($class_);

    return $this->currentFilename . ':' . $nameStr;
  }

  /**
   * @param Name $className
   * @param array $nodes
   * @return string
   */
  public function findClassId(Name $className, array $nodes):string {

    $qualifiedName = $this->getQualifiedName($className);
    $currentlyIncludedFiles = $this->getCurrentIncludedFiles();

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
   *
   * Helper method for adding included files when entering an
   * Include_ stmt;
   *
   * This will add any resolveable partial file names to the list
   * of currently included files.
   *
   * @param Include_ $node
   */
  public function enterInclude(Include_ $node) {
    $expr = $node->expr;

    while ($expr instanceof BinaryOp) {
      $expr = $expr->right;
    }

//    echo $this->filename . ":\n";
    if ($expr instanceof ConstFetch) {
//      echo "\tconst " . $expr->name->toString() . "\n";
    } else if ($expr instanceof String_) {
//      echo "\tstring " . $expr->value . "\n";
      $this->addIncludedFile($expr->value);
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

    } else if ($expr instanceof FuncCall) {

      if ($expr->name instanceof Name) {
        if ($expr->name->toString() === 'realpath') {

          $subExpr = $expr->args[0];

          while ($subExpr instanceof BinaryOp) {
            $subExpr = $subExpr->right;
          }

          if ($subExpr instanceof String_) {
//            echo "\tstring " . $subExpr->value . "\n";
            $this->addIncludedFile($subExpr->value);
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

}
