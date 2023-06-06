<?php

/**
* @brief      Scanner Trait
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Code;


use Exception;
use IPS\toolbox\Code\Utils\ParentVisitor;
use IPS\toolbox\extensions\toolbox\Scanner\Core;
use OutOfRangeException;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

use function mb_strtolower;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Scanner Class
*/
trait Scanner
{


    protected function getFullStop(): array
    {
        $default[] = (new Core())->fullStop();
        $extension = $this->app->extensions('toolbox', 'Scanner');
        if (empty($extension) === false) {
            $merged = [];
            foreach ($extension as $ext) {
                $data = $ext->fullStop();
                if ($data !== null) {
                    $default[] = $data;
                }
            }
        }
        return array_merge(...$default);
    }

    protected function getAutoLint(){
        $default[] = (new Core())->fullStop();
        $extension = $this->app->extensions('toolbox', 'Scanner');
        if (empty($extension) === false) {
            $merged = [];
            foreach ($extension as $ext) {
                $data = $ext->autoLint();
                if ($data !== null) {
                    $default[] = $data;
                }
            }
        }
        return array_merge(...$default);
    }

    protected function validationChecks(
        ReflectionClass $currentClass,
        Reflectionclass $parentClass,
        string $content,
        string $file,
        bool $hooks,
        &$warnings
    ) {
        //now lets get that money shot!
        foreach ($currentClass->getMethods() as $method) {
            if ($currentClass->getName() === $method->getDeclaringClass()->getName()) {
                //okay php is a bit moronic at times, trait methods that override parentclass methods,
                //show up as apart of the class being check, but there is no "real way" to check
                //if the method is from a trait/current class, so we are gonna get a bit dirty here
                //who doesn't like getting a bit dirty?
                //if this fails, it is likely a trait method and i'm not entirely sure how to handle them...
                //or should i handle them? yes...i'll handle them later
                if ($hooks === false && $method->getFileName() !== $currentClass->getFileName()) {
                    continue;
                }
                $parentName = $parentClass->getName();
                $methodName = $method->getName();
                $docComment = $method->getDocComment();
                //lets check if it is linted or autolinted, we use the parentclass for the class lookup part,
                //cause it is most likely the one that will be added here, instead of the subclass
                if (
                    isset($this->autoLint[$parentName][$methodName]) ||
                    mb_stristr($docComment, '@ips-lint ignore')
                ) {
                    continue;
                }
                try {
                    try {
                        //we are only interested in parent extend classes here
                        $originalMethod = $parentClass->getMethod($methodName);
                    } catch (Throwable $e) {
                        continue;
                    }

                    //we need to ignore the constructor for the most part, as it is the only method in php that can be overridden, not just overloaded
                    if (!str_contains($docComment, '@ips-lint ignore-signature') && $methodName !== '__construct') {
                        $this->validateSignature(
                            $method,
                            $originalMethod,
                            $file,
                            $warnings
                        );
                    }

                    //we need to ignore the constructor for the most part, as it is the only method in php that can be overridden, not just overloaded
                    if (!str_contains($docComment, '@ips-lint ignore-parameters') && $methodName !== '__construct') {
                        $this->validateParameters(
                            $method,
                            $originalMethod,
                            $file,
                            $warnings
                        );
                    }

                    if (!str_contains($docComment, '@ips-lint ignore-parent')) {
                        try {
                            try {
                                //let's see if the methods that exist in the parent class, are getting called here!
                                $parentUsages = $this->findParentUsages($method, $content);
                            } catch (OutOfRangeException $e) {
                                $parentUsages = [];
                            }
                            $methodName = mb_strtolower($method->getName());
                            if (!isset($parentUsages[$methodName])) {
                                $path = $this->buildPath($file, $method->getStartLine());
                                $warnings['parentUsage'][] = [
                                    'error' => "Does not call parent",
                                    'path' => ['url' => $path, 'name' => $file],
                                    'line' => $method->getStartLine(),
                                    'method' => $method->getName()
                                ];
                            }
                        } catch (Throwable $e) {
                        }
                    }
                } catch (Throwable $e) {
                    $warnings['errors'][] = [
                        'error' => $e->getMessage(),
                        'path' => [
                            'url' => $this->buildPath($file, 0),
                            'name' => $file
                        ],
                        'line' => $e->getLine(),
                        'method' => $method->getName()
                    ];
                }
            }
        }
    }

    public function validateSignature(
        ReflectionMethod $currentMethod,
        ReflectionMethod $originalMethod,
        $currentFileName,
        &$warnings
    ) {
        $errors = [];
        $methodName = $currentMethod->getName();
        $currentMethodStartLine = $currentMethod->getStartLine();
        //here we are building an editor path, so if you are using an editors protocol like phpstorm has available
        $path = $this->buildPath(
            $currentFileName,
            $currentMethodStartLine
        );
        $currentFileName = str_replace($this->app->getApplicationPath(), '', $currentFileName);

        //this might not be needed, but check if the parent is private
        if ($originalMethod->isPrivate()) {
            $errors[] = "Method's visibility in parent is private";
        }

        //check if they have switched visibility
        if (
            ($originalMethod->isPublic() !== $currentMethod->isPublic()) ||
            ($originalMethod->isProtected() !== $currentMethod->isProtected())
        ) {
            $errors[] = "Method's visibility mismatch";
        }

        //lets see if they changed it from a static method to a instance method
        if ($originalMethod->isStatic() && !$currentMethod->isStatic()) {
            $errors[] = "Method should be static";
        }

        if (!$originalMethod->isStatic() && $currentMethod->isStatic()) {
            $errors[] = "Method should not be static";
        }

        if ($originalMethod->hasReturnType() && !$currentMethod->hasReturnType()) {
            $errors[] = "Method is missing return type";
        }

        if (!$originalMethod->hasReturnType() && $currentMethod->hasReturnType()) {
            $errors[] = "Method return type mismatch";
        }

        if(empty($errors) === false){
            foreach($errors as $ii => $error){
                $warnings['signature'][] = [
                    'error' => $error,
                    'path' => ['url' => $path, 'name' => $currentFileName],
                    'line' => $currentMethodStartLine,
                    'method' => $methodName
                ];
            }
        }
    }

    protected function validateParameters(
        ReflectionMethod $currentMethod,
        ReflectionMethod $originalMethod,
        $currentFileName,
        &$warnings
    ) {
        $methodName = $currentMethod->getName();
        $currentMethodStartLine = $currentMethod->getStartLine();
        //here we are building an editor path, so if you are using an editors protocol like phpstorm has available
        $path = $this->buildPath(
            $currentFileName,
            $currentMethodStartLine
        );
        $currentFileName = str_replace($this->app->getApplicationPath(), '', $currentFileName);
        $zipped = array_map(null, $currentMethod->getParameters(), $originalMethod->getParameters());
        /** @var $param ReflectionParameter[] */
        foreach ($zipped as $param) {
            $errors = [];
            if ($param[0] === null) {
                $extraParams = array_slice($originalMethod->getParameters(), $param[1]->getPosition());
                $paramNames = [];
                /** @var $extraParam ReflectionParameter */
                foreach ($extraParams as $extraParam) {
                    $paramNames[] = $extraParam->getName();
                }
                $paramNamesString = implode(", ", $paramNames);
                $errors[] = "Missing Parameter: {$paramNamesString}";
            }

            if (isset($param[0]) && !$param[0]->isOptional()) {
                if (isset($param[1]) && $param[1] === null) {
                    $errors[] = "Parameter \${$param[0]->getName()} is required but missing.";
                }
                if (isset($param[1]) && $param[1]->isOptional()) {
                    $errors[] = "Parameter \${$param[0]->getName()} is required but set as optional";
                }
            }

            if(isset($param[1]) &&
                $param[1]->isPassedByReference() &&
                isset($param[0]) &&
                !$param[0]->isPassedByReference()
            ){
                $errors[] = "Parameter \${$param[1]->getName()} is passed by reference in parent.";
            }

            if(isset($param[1]) && $param[1] !== null && $param[1]->isOptional()) {
                $currentDefault = null;
                try {
                    $hookDefault = $param[0]->getDefaultValue();
                }catch(Throwable $e){}
                $originalDefault = null;
                try {
                    $originalDefault = $param[1]->getDefaultValue();
                }catch(Throwable $e){
                }
                if ($hookDefault !== $originalDefault) {
                    $errors[] = "Parameter \${$param[0]->getName()} mismatched default value.";
                }
            }

            if (isset($param[0]) && $param[0]->hasType() && $param[1] && !$param[1]->hasType()) {
                $errors[] = "Parameter \${$param[0]->getName()} parameter type/hint mismatch.";
            }

            if (
                isset($param[0]) &&
                isset($param[1]) &&
                $param[1] &&
                $param[0]->getName() !== $param[1]->getName()
            ) {
                $errors[] = "Parameter \${$param[0]->getName()} name mismatch.";
            }

            if (
                !$param[1] &&
                $param[0]
            ) {
                $errors[] = "Parameter \${$param[0]->getName()} not in parent.";
            }

            if(empty($errors) === false){
                foreach($errors as $ii => $error){
                    $warnings['signature'][] = [
                        'error' => $error,
                        'path' => ['url' => $path, 'name' => $currentFileName],
                        'line' => $currentMethodStartLine,
                        'method' => $methodName
                    ];
                }
            }
        }
    }

    protected function findParentUsages(ReflectionMethod $method, string $content): array
    {
        $methodBody = $this->extractLines(
            $content,
            $method->getStartLine(),
            $method->getEndLine()
        );
        $name = $method->getName();
        $firstLineNum = $method->getStartLine();
        $lexer = new Lexer(['usedAttributes' => ['startLine']]);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
        try {
            $ast = $parser->parse("<?php class _fake_class_ {\n{$methodBody}\n}");
        } catch (Exception $e) {
            throw new OutOfRangeException(
                $e->getMessage() . ' Method: ' . $name . ' File: ' . $method->getDeclaringClass()->getFileName()
            );
        }
        $visitor = new ParentVisitor($firstLineNum - 1);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getParentCalls();
    }

    public function extractLines(string $str, int $startLineInclusive, int $endLineInclusive): ?string {
        $startLine = $startLineInclusive - 1;
        $numLines = $endLineInclusive - $startLine;
        preg_match("/^(?:.*\n){{$startLine}}((?:.*\n){{$numLines}})/", $str, $matches);
        return $matches[1] ?? null;
    }
}
