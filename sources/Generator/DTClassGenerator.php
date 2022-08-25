<?php

/**
 * @brief       DTClassGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.2.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Generator;

use IPS\toolbox\Application;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Generator\PromotedParameterGenerator;

use function array_diff;
use function defined;
use function header;
use function preg_replace;
use function method_exists;
use function strtolower;
Application::loadAutoLoader();

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _DTClassGenerator
 *
 * @package IPS\toolbox\DevCenter\Sources\Generator
 * @mixin \IPS\toolbox\DevCenter\Sources\Generator\DTClassGenerator
 */
class _DTClassGenerator extends ClassGenerator
{
    private const CONSTRUCTOR_NAME  = '__construct';

    public static function fromReflection(ClassReflection $classReflection)
    {
        $cg = new static($classReflection->getName());

        $cg->setSourceContent($cg->getSourceContent());
        $cg->setSourceDirty(false);

        if ($classReflection->getDocComment() !== '') {
            $cg->setDocBlock(DocBlockGenerator::fromReflection($classReflection->getDocBlock()));
        }

        $cg->setAbstract($classReflection->isAbstract());

        // set the namespace
        if ($classReflection->inNamespace()) {
            $cg->setNamespaceName($classReflection->getNamespaceName());
        }

        /* @var ClassReflection $parentClass */
        $parentClass = $classReflection->getParentClass();
        $interfaces = $classReflection->getInterfaces();

        if ($parentClass) {
            $cg->addUse($parentClass->getName());
            $cg->setExtendedClass($parentClass->getName());
            $interfaces = array_diff($interfaces, $parentClass->getInterfaces());
        }

        $interfaceNames = [];
        foreach ($interfaces as $interface) {
            $cg->addUse($interface);
            /* @var ClassReflection $interface */
            $interfaceNames[] = $interface->getName();
        }

        $cg->setImplementedInterfaces($interfaceNames);

        $properties = [];

        foreach ($classReflection->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getName() === $classReflection->getName()) {
                $properties[] = PropertyGenerator::fromReflection($reflectionProperty);
            }
        }

        $cg->addProperties($properties);

        $constants = [];

        foreach ($classReflection->getReflectionConstants() as $constReflection) {
            $constants[] = [
                'name'    => $constReflection->getName(),
                'value'   => $constReflection->getValue(),
                'isFinal' => method_exists($constReflection, 'isFinal')
                    ? $constReflection->isFinal()
                    : false,
            ];
        }

        $cg->addConstants($constants);

        $methods = [];

        foreach ($classReflection->getMethods() as $reflectionMethod) {
            $className     = $cg->getName();
            $namespaceName = $cg->getNamespaceName();
            if ($namespaceName !== null) {
                $className = $namespaceName . '\\' . $className;
            }

            if ($reflectionMethod->getDeclaringClass()->getName() == $className) {
                $method = MethodGenerator::fromReflection($reflectionMethod);

                if (self::CONSTRUCTOR_NAME === strtolower($method->getName())) {
                    foreach ($method->getParameters() as $parameter) {
                        if ($parameter instanceof PromotedParameterGenerator) {
                            $cg->removeProperty($parameter->getName());
                        }
                    }
                }

                $methods[] = $method;
            }
        }

        $cg->addMethods($methods);

        return $cg;
    }

    public function generate()
    {
        $this->addUse('function defined');
        $this->addUse('function header');
        $parent = parent::generate();
        $addIn = <<<'eof'
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}
eof;

        $parent = preg_replace('/namespace(.+?)([^\n]+)/', 'namespace $2' . self::LINE_FEED . self::LINE_FEED . $addIn,
            $parent);

        return $parent;
    }

}
