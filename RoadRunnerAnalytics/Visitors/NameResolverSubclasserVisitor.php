<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 4/10/17
 * Time: 1:19 PM
 */

namespace RoadRunnerAnalytics\Visitors;


use PhpParser\Node;
use PhpParser\NodeVisitor\NameResolver;
use Psr\Log\LoggerInterface;

class NameResolverSubclasserVisitor extends NameResolver
{

  /**
   * @var LoggerInterface
   */
  private $logger;

  private $uniqueClasses = [];

  /**
   * @param LoggerInterface $logger
   */
  public function setLogger(LoggerInterface $logger)
  {

    $this->logger = $logger;

    return $this;
  }

  /**
   *
   * Do everything that the namspace resolver does. Wrap the results in
   * a node that has a property type for `->namespacedName`
   *
   * @param Node $node
   * @return null|Node|void
   */
  public function enterNode(Node $node)
  {
    $returnValue = parent::enterNode($node);

    if (!empty($node->namespacedName)) {

      $className = get_class($node);

      $this->uniqueClasses[$className] = $className;
    }

    return $returnValue;
  }

  /**
   * PHP 5 introduces a destructor concept similar to that of other object-oriented languages, such as C++.
   * The destructor method will be called as soon as all references to a particular object are removed or
   * when the object is explicitly destroyed or in any order in shutdown sequence.
   *
   * Like constructors, parent destructors will not be called implicitly by the engine.
   * In order to run a parent destructor, one would have to explicitly call parent::__destruct() in the destructor body.
   *
   * Note: Destructors called during the script shutdown have HTTP headers already sent.
   * The working directory in the script shutdown phase can be different with some SAPIs (e.g. Apache).
   *
   * Note: Attempting to throw an exception from a destructor (called in the time of script termination) causes a fatal error.
   *
   * @return void
   * @link http://php.net/manual/en/language.oop5.decon.php
   */
  function __destruct()
  {
    var_dump(array_keys($this->uniqueClasses));
  }


}