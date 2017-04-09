<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 10:19 AM
 */

namespace RoadRunnerAnalytics\Nodes;


use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;

class ResolvedKeywordsStaticCall extends StaticCall implements ResolvedKeywordsNode
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
   * @param StaticCall $node
   * @return ResolvedKeywordsStaticCall
   */
  public static function fromStaticCall(StaticCall $node): ResolvedKeywordsStaticCall {
    return new ResolvedKeywordsStaticCall($node->class, $node->name, $node->args, $node->attributes);
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