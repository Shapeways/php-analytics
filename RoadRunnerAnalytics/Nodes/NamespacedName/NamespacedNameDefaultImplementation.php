<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 3:31 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;


use Exception;
use PhpParser\Node\Name;

trait NamespacedNameDefaultImplementation
{

  /**
   * @var Name
   */
  private $namespacedName;

  /**
   * @param Name $namespacedName
   * @return NamespacedNameNode
   * @throws Exception
   */
  public function setNamespacedName(Name $namespacedName): NamespacedNameNode
  {
    $this->namespacedName = $namespacedName;

    if ($this instanceof NamespacedNameNode) {
      return $this;
    }

    throw new Exception('Implementation for NamespacedNameNode does not implement NamespacedNameNode');
  }

  /**
   * @return Name
   */
  public function getNamespacedName(): Name {
    return $this->namespacedName;
  }

}