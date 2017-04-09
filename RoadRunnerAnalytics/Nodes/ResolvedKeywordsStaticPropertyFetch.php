<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 11:56 AM
 */

namespace RoadRunnerAnalytics\Nodes;


use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;

class ResolvedKeywordsStaticPropertyFetch extends StaticPropertyFetch implements ResolvedKeywordsNode
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
   * @param StaticPropertyFetch $node
   * @return ResolvedKeywordsStaticPropertyFetch
   */
  public static function fromStaticPropertyFetch(StaticPropertyFetch $node): ResolvedKeywordsStaticPropertyFetch {
    return new ResolvedKeywordsStaticPropertyFetch($node->class, $node->name, $node->attributes);
  }

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

    return $this;
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

    return $this;
  }

}