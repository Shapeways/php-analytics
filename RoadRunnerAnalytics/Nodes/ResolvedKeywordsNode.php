<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/9/17
 * Time: 10:21 AM
 */

namespace RoadRunnerAnalytics\Nodes;


use PhpParser\Node\Name;

interface ResolvedKeywordsNode
{
  const KEYWORD_SELF    = 'self';
  const KEYWORD_PARENT  = 'parent';
  const KEYWORD_STATIC  = 'static';

  /**
   *
   * Returns the keyword that was resolved
   *
   * @return string
   */
  public function getResolvedKeyword(): string;

  /**
   *
   * Sets the keyword that was resolved
   *
   * @param string $resolvedKeyword
   * @return ResolvedKeywordsNode
   */
  public function setResolvedKeyword(string $resolvedKeyword): ResolvedKeywordsNode;

  /**
   *
   * Gets the class name that they keyword was resolved to
   *
   * @return Name
   */
  public function getResolvedClass(): Name;

  /**
   *
   * Gets the class name that they keyword was resolved to
   *
   * @param Name $resolvedClass
   * @return ResolvedKeywordsNode
   */
  public function setResolvedClass(Name $resolvedClass): ResolvedKeywordsNode;
}