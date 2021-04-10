<?php

namespace Go\ParserReflection\Stub;

use Generator;

trait SimpleTrait
{
    public function foo() { return __CLASS__; }
}

trait ConflictedSimpleTrait
{
    public function foo() { return 'BAZ'; }
}

trait TraitWithProperties
{
    public static $cs = 'foo';
    protected static $bs = __TRAIT__;
    private static $as = 1;
    public $c = 'baz';
    protected $b = 'bar';
    private $a = 'foo';
}

interface SimpleInterface { }

interface InterfaceWithMethod
{
    public function foo();
}

interface AbstractInterface
{
    public function foo();

    public function bar();
}

abstract class ExplicitAbstractClass { }

abstract class ImplicitAbstractClass
{
    public $c = 'baz';
    protected $b = 'bar';
    private $a = 'foo';

    public abstract function test();
}

/**
 * Some docblock for the class
 */
final class FinalClass
{
    public $args = [];

    public function __construct($a = null, &$b = null)
    {
        $this->args = array_slice(array($a, &$b), 0, func_num_args());
    }
}

class BaseClass
{
    protected static function prototypeMethod()
    {
        return __CLASS__;
    }
}

/**
 * @link https://bugs.php.net/bug.php?id=70957 self::class can not be resolved with reflection for abstract class
 */
abstract class AbstractClassWithMethods extends BaseClass
{
    public const TEST = 5;

    public function __construct() { }

    public static function staticFunc() { }

    /**
     * @return string
     */
    public static function funcWithDocAndBody()
    {
        static $a = 5, $test = '1234';

        return 'hello';
    }

    public static function funcWithReturnArgs($a, $b = 100, $c = 10.0)
    {
        return [$a, $b, $c];
    }

    public static function prototypeMethod()
    {
        return __CLASS__;
    }

    protected static function protectedStaticFunc() { }

    public function __destruct() { }

    public function explicitPublicFunc() { }

    public function implicitPublicFunc() { }

    public abstract function abstractFunc();

    public final function finalFunc() { }

    /**
     * @return Generator
     */
    public function generatorYieldFunc()
    {
        $index = 0;
        while ($index < 1e3) {
            yield $index;
        }
    }

    /**
     * @return int
     */
    public function noGeneratorFunc()
    {
        $gen = function () {
            yield 10;
        };

        return 10;
    }

    protected function protectedFunc() { }

    private function privateFunc() { }

    private function testParam($a, $b = null, $d = self::TEST) { }
}

class ClassWithProperties
{
    public static $publicStaticProperty = M_PI;
    protected static $protectedStaticProperty = 'foo';
    /**
     * Some message to test docBlock
     *
     * @var int
     */
    private static $privateStaticProperty = 1;
    public $publicProperty = 42.0;
    protected $protectedProperty = 'a';
    private $privateProperty = 123;
}

/*
 * Current implementation returns wrong __toString description for the parent methods
 * @see https://github.com/goaop/parser-reflection/issues/55
abstract class SimpleAbstractInheritance extends ImplicitAbstractClass
{
    public $b = 'bar1';
    public $d = 'foobar';
    private $e = 'foobaz';
}
*/

abstract class ClassWithMethodsAndProperties
{
    public static $staticPublicProperty;
    protected static $staticProtectedProperty;
    private static $staticPrivateProperty;
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;

    public static function publicStaticMethod() { }

    protected static function protectedStaticMethod() { }

    private static function privateStaticMethod() { }

    public function publicMethod() { }

    abstract public function publicAbstractMethod();

    final public function publicFinalMethod() { }

    protected function protectedMethod() { }

    abstract protected function protectedAbstractMethod();

    final protected function protectedFinalMethod() { }

    private function privateMethod() { }

    final private function privateFinalMethod() { }
}

class SimpleInheritance extends ExplicitAbstractClass { }

/*
 * Current implementation doesn't support trait adaptation,
 * @see https://github.com/goaop/parser-reflection/issues/54
 *
class ClassWithTraitAndAdaptation
{
    use SimpleTrait {
        foo as protected fooBar;
        foo as private fooBaz;
    }
}

class ClassWithTraitAndConflict
{
    use SimpleTrait, ConflictedSimpleTrait {
        foo as protected fooBar;
        ConflictedSimpleTrait::foo insteadof SimpleTrait;
    }
}
*/

/*
 * Logic of prototype methods for interface and traits was changed since 7.0.6
 * @see https://github.com/goaop/parser-reflection/issues/56
class ClassWithTraitAndInterface implements InterfaceWithMethod
{
    use SimpleTrait;
}
*/

class ClassWithInterface implements SimpleInterface { }

class ClassWithTrait
{
    use SimpleTrait;
}

class NoCloneable
{
    private function __clone() { }
}

class NoInstantiable
{
    private function __construct() { }
}

class ClassWithScalarConstants
{
    public const A1 = 11;
    public const B = 42.0;
    public const C = 'foo';
    public const D = false;
    public const E = null;
}

const NS_CONST = 'test';

class ClassWithMagicConstants
{
    public const A = __DIR__;
    public const B = __FILE__;
    public const C = __NAMESPACE__;
    public const D = __CLASS__;
    public const E = __LINE__;

    public static $a = self::A;
    protected static $b = self::B;
    private static $c = self::C;
}

class ClassWithConstantsAndInheritance extends ClassWithMagicConstants
{
    public const A = 'overridden';
    public const H = M_PI;
    public const J = NS_CONST;

    public static $h = self::H;
}
