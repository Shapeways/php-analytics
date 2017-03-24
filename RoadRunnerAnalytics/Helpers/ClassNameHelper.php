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
use PhpParser\Node\Stmt\ClassLike;
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
  private $currentNamespace;

  /**
   * @var string[]
   */
  private $currentIncludedFiles = array();

  /**
   * ClassNameHelper constructor.
   */
  public function __construct()
  {
    $this->rootNamespace = new Namespace_(
      new Name('')
    );
    $this->currentNamespace = [$this->rootNamespace];
  }

  /**
   * @return $this
   */
  public function resetCurrentUse(): ClassNameHelper {
    $this->currentUse = [];
    return $this;
  }

  /**
   * @param UseUse $node
   * @return $this
   */
  public function setCurrentUse(UseUse $node): ClassNameHelper {
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
  public function resetCurrentNamespace(): ClassNameHelper {
    $this->currentNamespace = [$this->rootNamespace];
    return $this;
  }

  /**
   * @param Namespace_ $node
   */
  public function pushCurrentNamespace(Namespace_ $node): ClassNameHelper {
    array_push($this->currentNamespace, $node);

    return $this;
  }

  /**
   * @param Namespace_ $node
   * @return Namespace_
   * @throws Exception
   */
  public function popCurrentNamespace(Namespace_ $node): Namespace_ {

    $poppedCurrentNamespace = array_pop($this->currentNamespace);

    if (($poppedCurrentNamespace !== $node)
      || ($poppedCurrentNamespace === null)
    ) {
      throw new Exception("Unmatched class depth");
    }

    return $poppedCurrentNamespace;
  }

  /**
   * @return string
   */
  public function peekCurrentNamespace(): string {
    $peekedNamespace = end($this->currentNamespace);

    // Special case for Root Namespace
    if ($peekedNamespace === $this->rootNamespace) {
      return '';
    }

    return $peekedNamespace->name->toString() . '\\';
  }

  /**
   * @param ClassLike $node
   * @return string
   */
  public function getQualifiedNameForClassLike(ClassLike $node): string {
    $nameStr = $node->name;

    if ($this->getCurrentUse($nameStr)) {
      return $this->getCurrentUse($nameStr);
    }

    $nameStr = $this->peekCurrentNamespace() . $nameStr;

    return $nameStr;
  }

  /**
   * @param Name $name
   * @return string
   */
  public function getQualifiedName(Name $name): string {

    $nameStr = $name->toString();
    if ($name->isUnqualified()) {

      if (!empty($this->getCurrentUse($nameStr))) {
        return $this->getCurrentUse($nameStr);
      }

      return $this->peekCurrentNamespace() . $nameStr;
    }
    else if ($name->isFullyQualified()) {
      return $nameStr;
    }

    return $nameStr;
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
}
