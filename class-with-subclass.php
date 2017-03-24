<?php
/**
 * Created by PhpStorm.
 * User: wjohnald
 * Date: 12/31/16
 * Time: 4:42 PM
 */



namespace Wes;

interface Y {

}

class ParentClass implements Y {

  private $child;

  public function __construct(ChildClass $child, $options)
  {

    $this->child = $child;

  }

  public function doThing(OtherClass $otherClass) {

    OtherClass::otherClassStaticMethod(ChildClass::$property);

    OtherClass::otherClassStaticMethod(ChildClass::WES);

  }

}

interface X extends \Z {

}

class ChildClass {

  const WES = 'wes';

  static $property = 'wes';

}

class OtherClass extends ChildClass implements X {

  public static function otherClassStaticMethod($property) {

  }

}

class Class_ {

}

class NamespaceExtension extends \PhpParser\Node\Scalar\MagicConst\Class_ {

}