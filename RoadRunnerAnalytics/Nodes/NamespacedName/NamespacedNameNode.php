<?php

/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 3:28 PM
 */

namespace RoadRunnerAnalytics\Nodes\NamespacedName;

use PhpParser\Node\Name;

interface NamespacedNameNode
{
  /**
   *
   * Set the namespacedName for a node
   *
   * @param string $namespacedName
   * @return NamespacedNameNode
   */
  public function setNamespacedName(Name $namespacedName): NamespacedNameNode;

  /**
   *
   * Get the namespacedName of a node
   *
   * @return string
   */
  public function getNamespacedName(): Name;

}