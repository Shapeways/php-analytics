<?php


/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/24/17
 * Time: 11:29 AM
 */


namespace RoadRunnerAnalytics\Helpers;

use Exception;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;

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
   * @var UseUse[]
   */
  private $currentUse = [];

  /**
   * @var Namespace_
   */
  private $rootNamespace;

  /**
   * @var Namespace_[]
   */
  private $currentNamespace = [];

  /**
   * ClassNameHelper constructor.
   */
  public function __construct()
  {
    $this->rootNamespace = new Namespace_(
      new Name('')
    );
  }

  /**
   * @return $this
   */
  public function resetCurrentUse() {
    $this->currentUse = [];
    return $this;
  }

  /**
   * @param UseUse $node
   * @return $this
   */
  public function setCurrentUse(UseUse $node) {
    $this->currentUse[$node->alias] = $node->name->toString();

    return $this;
  }

  /**
   * @param string $alias
   * @return null|UseUse
   */
  public function getCurrentUse(string $alias) {
    return $this->currentUse[$alias]?? null;
  }

  /**
   * @return $this
   */
  public function resetCurrentNamespace() {
    $this->currentNamespace = [$this->rootNamespace];
    return $this;
  }

  /**
   * @param Namespace_ $node
   */
  public function pushCurrentNamespace(Namespace_ $node) {
    array_push($this->currentNamespace, $node);
  }

  /**
   * @return Namespace_
   */
  public function popCurrentNamespace() {

    $poppedCurrentNamespace = array_pop($this->currentNamespace);

    if ($poppedCurrentNamespace === null) {
      throw new Exception("Unmatched class depth");
    }

    return $poppedCurrentNamespace;
  }

  /**
   * @return mixed|Namespace_
   */
  public function peekCurrentNamespace() {
    $peekedNamespace = end($this->currentNamespace);
    return $peekedNamespace? $peekedNamespace->name->toString() : '';
  }

}
