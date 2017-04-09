<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 12:24 PM
 */

namespace RoadRunnerAnalytics\Nodes;


use Exception;
use PhpParser\Node\Name;

trait ResolvedKeywordsDefaultImplementation
{
  /**
   * @var string
   */
  private $resolvedKeyword = '';

  /**
   * @var Name
   */
  private $resolvedClass;

  /**
   *
   * Returns the keyword that was resolved
   *
   * @return string
   */
  public function getResolvedKeyword(): string
  {
    return $this->resolvedKeyword;
  }

  /**
   *
   * Sets the keyword that was resolved
   *
   * @param string $resolvedKeyword
   * @return ResolvedKeywordsNode
   */
  public function setResolvedKeyword(string $resolvedKeyword): ResolvedKeywordsNode
  {
    $this->resolvedKeyword = $resolvedKeyword;

    if ($this instanceof ResolvedKeywordsNode) {
      return $this;
    }

    throw new Exception('Implementation for ResolvedKeywordsNode does not implement ResolvedKeywordsNode');
  }

  /**
   *
   * Gets the class name that they keyword was resolved to
   *
   * @return Name
   */
  public function getResolvedClass(): Name
  {
    return $this->resolvedClass;
  }

  /**
   *
   * Gets the class name that they keyword was resolved to
   *
   * @param Name $resolvedClass
   * @return ResolvedKeywordsNode
   */
  public function setResolvedClass(Name $resolvedClass): ResolvedKeywordsNode
  {
    $this->resolvedClass = $resolvedClass;

    if ($this instanceof ResolvedKeywordsNode) {
      return $this;
    }

    throw new Exception('Implementation for ResolvedKeywordsNode does not implement ResolvedKeywordsNode');
  }
}