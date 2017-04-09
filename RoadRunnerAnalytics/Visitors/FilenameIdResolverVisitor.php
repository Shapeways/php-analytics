<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/24/17
 * Time: 1:39 PM
 */

namespace RoadRunnerAnalytics\Visitors;


use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;

class FilenameIdResolverVisitor extends NodeVisitorAbstract
{

  /**
   * @var string
   */
  private $filename;

  public function __construct(string $filename)
  {
    $this->filename = $filename;
  }

  /**
   * @return mixed
   */
  public function getFilename()
  {

    return $this->filename;
  }

  /**
   * @param mixed $filename
   * @return FilenameIdResolverVisitor
   */
  public function setFilename($filename): FilenameIdResolverVisitor
  {

    $this->filename = $filename;

    return $this;
  }

  /**
   * @param Node $node
   */
  public function enterNode(Node $node) {

    if ($node instanceof ClassLike) {
      /**
       * @var $classLikeName Name
       */
      $classLikeName = $node->namespacedName?? $node->name;

      $node->filenameId =  $this->filename . ':' . $classLikeName->toString();
    }


  }

}