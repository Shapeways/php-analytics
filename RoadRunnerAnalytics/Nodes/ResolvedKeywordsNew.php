<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 3/27/17
 * Time: 5:46 PM
 */


namespace RoadRunnerAnalytics\Nodes;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;

class ResolvedKeywordsNew extends New_
{

  const KEYWORD_SELF    = 'self';
  const KEYWORD_PARENT  = 'parent';
  const KEYWORD_STATIC  = 'static';

  /**
   * @var string
   */
  private $resolvedKeyword = '';

  /**
   * @var Name
   */
  private $resolvedClass;

  public static function fromNew_(New_ $node): ResolvedKeywordsNew {
    return new ResolvedKeywordsNew($node->class, $node->args, $node->attributes);
  }

  /**
   * @return string
   */
  public function getResolvedKeyword(): string
  {

    return $this->resolvedKeyword;
  }

  /**
   * @param string $resolvedKeyword
   * @return ResolvedKeywordsNew
   */
  public function setResolvedKeyword(string $resolvedKeyword): ResolvedKeywordsNew
  {

    $this->resolvedKeyword = $resolvedKeyword;

    return $this;
  }

  /**
   * @return Name
   */
  public function getResolvedClass(): Name
  {

    return $this->resolvedClass;
  }

  /**
   * @param Name $resolvedClass
   * @return ResolvedKeywordsNew
   */
  public function setResolvedClass(Name $resolvedClass): ResolvedKeywordsNew
  {

    $this->resolvedClass = $resolvedClass;

    return $this;
  }


}