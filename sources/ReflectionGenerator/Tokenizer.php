<?php

/**
 * @brief       Tokenizer Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\ReflectionGenerator;


use Go\ParserReflection\ReflectionMethod;
use Go\ParserReflection\ReflectionProperty;
use ReflectionClass;

use function explode;
use function implode;
use function ltrim;
use function method_exists;
use function rtrim;
use function trim;

class _Tokenizer
{
    /**
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * @var array
     */
    protected $content;
    /**
     * @throws \ReflectionException
     */
    public function __construct(string $class) {
        $this->reflection = new ReflectionClass($class);
        $filename = $this->reflection->getParentClass()->getFileName();
        $this->content = explode("\n",\file_get_contents($filename));
    }

    public function build(){
        $this->buildMethods();
        $this->buildProperties();
        $this->buildTraits();
        _p($this->reflection->getConstants());
    }

    public function buildTraits(){
        $traits = $this->reflection->getTraits();
         /** @var Reflection $trait */
        foreach($traits as $trait){

        }
    }

    public function buildProperties(){
        $props = $this->reflection->getProperties();
        $source = $this->content;
        $properties = [];
        /** @var ReflectionProperty $prop */
        foreach($props as $prop){
            if(!method_exists($prop, 'getDefaultValue')){
                throw new \Exception('must be using php 8.0+');
            }
            $properties[] = [
                'name' => $prop->getName(),
                'value' => $prop->getDefaultValue(),
                'public' => $prop->isPublic(),
                'private' => $prop->isPrivate(),
                'protected' => $prop->isProtected(),
                'static' => $prop->isStatic(),
                'doc' => $prop->getDocComment(),
                'type' => $prop->getType()
            ];
        }
    }

    public function buildMethods(){
        $funcs = $this->reflection->getMethods();
        $source = $this->content;
        $methods = [];
        /** @var ReflectionMethod $func */
        foreach($funcs as $func) {
            $start = $func->getStartLine(); // it's actually - 1, otherwise you wont get the function() block
            $end = $func->getEndLine();
            $length = $end - $start;

            $body = \trim(\implode("\n", \array_slice($source, $start, $length)));
            $first = \mb_substr($body, 0, 1);
            $last = \mb_substr($body, -1);
            if ($first === '{') {
                $body = \trim(\ltrim($body, '{'));
            }
            if ($last === '}') {
                $body = \trim(\rtrim($body, '}'));
            }
            $methods[] = [
                'name' => $func->getName(),
                'parmas'=>  $func->getParameters(),
                'body' => $body,
                'doc' => $func->getDocComment(),
                'hint' => $func->getModifiers(),
                'static' => $func->isStatic(),
                'public' => $func->isPublic(),
                'protected' => $func->isProtected(),
                'private' => $func->isPrivate(),
                'final' => $func->isFinal(),
                'return' => $func->getReturnType()
            ];
        }
    }
}
