<?php

/**
* @brief      Hooks Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.10
* @version    -storm_version-
*/

namespace IPS\toolbox\Code;

use InvalidArgumentException;
use IPS\toolbox\Code\ParserAbstract;
use IPS\toolbox\Code\Utils\Hook;
use IPS\toolbox\Code\Utils\HookClass;
use IPS\toolbox\Code\Utils\ParentVisitor;
use OutOfBoundsException;
use OutOfRangeException;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function _d;
use function print_r;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Hooks Class
* @mixin Hooks
*/
class _Hooks extends ParserAbstract
{
    protected $hookFile;
    protected $existingHooks = [];

    protected function getAppPath()
    {
        $appPath = parent::getAppPath();
        $this->hookFile = \json_decode(\file_get_contents($appPath.'data/hooks.json'),true);
        if(empty($this->hookFile) === true){
            throw new InvalidArgumentException();
        }
        return $appPath.'hooks/';
    }

    protected function getFiles()
    {
        $files = new Finder();
        $files->in($this->getAppPath())->name('*.php');
        if ($this->skip !== null) {
            foreach ($this->skip as $name) {
                $files->notName($name);
            }
        }
        $this->files = $files->files();
    }

    public function exist(){
        $warnings = [];
        foreach($this->files as $file){
            try {
                $this->identify($file);
            }
            catch(\OutOfBoundsException $e){
                $name = $file->getBasename('.php');
                $warnings[$name] = $e->getMessage();
            }
        }

        return $warnings;
    }

    public function validate(){
        $warnings = [
            'parse' => [],
            'processing' => [],
            'signature' => [],
            'parameters' => [],
            'parent' => [],
            'parentUsage' => [],
            'errors' => []
        ];
        /** @var Hook $hook */
        foreach($this->existingHooks as $hook) {
            if ($hook->isThemHook() === true) {
                continue;
            }
            $newName = uniqid('hook_', false);
            $content = preg_replace(
                '/class \S+ extends _HOOK_CLASS_/',
                "class {$newName} extends \\IPS\\toolbox\\Code\\Utils\\HookClass",
                $hook->getContent()
            );
            try {
                @eval($content);
            } catch (\ParseError $e) {
                $warnings['parse'][] = [
                    'file' => $hook->name(),
                    'path' => $hook->path(),
                    'error' => $e->getMessage()
                ];
                continue;
            }

            try {
                $hookClass = new \ReflectionClass($newName);
            } catch (\ReflectionException $e) {
                $warnings['processing'][] = [
                    'file' => $hook->name(),
                    'path' => $hook->path(),
                    'error' => $e->getMessage()
                ];
                continue;
            }

            if (mb_stristr($hookClass->getDocComment(), '@ips-lint ignore')) {
                return [];
            }

            try {
                $originalClass = new \ReflectionClass($hook->getClass());
            } catch (\ReflectionException $e) {
                $warnings['parent'][] = [
                    'file' => $hook->name(),
                    'path' => $hook->path(),
                    'error' => $e->getMessage(),
                    'class' => $hook->getClass()
                ];
                continue;
            }

            foreach ($hookClass->getMethods() as $hookMethod) {
                if (!mb_stristr($hookMethod->getDocComment(), '@ips-lint ignore')) {
                    try {
                        $originalMethod = $originalClass->getMethod($hookMethod->getName());
                        $result = $this->validateHookSignature(
                            $hookMethod,
                            $originalMethod,
                            $hook
                        );
                        if ($result !== null) {
                            $warnings['signature'][] = $result;
                        }
                        $result = $this->validateParameters($hookMethod, $originalMethod, $hook);
                        if($result !== null){
                            $warnings['parameters'][] = $result;
                        }
                    } catch (\ReflectionException $e) {
                    }
                }
                $methodBody = static::extractLines(
                    $content,
                    $hookMethod->getStartLine(),
                    $hookMethod->getEndLine());
                try {
                    $parentUsages = $this->findParentUsages($hook,$hookMethod->getName(),$methodBody, $hookMethod->getStartLine());
                    foreach ($parentUsages as $parentUsage) {
                        if (!$originalClass->hasMethod($parentUsage['method'])) {
                            $path = $this->buildPath($hook->path(), $parentUsage['line']);
                            $warnings['parentUsage'][] = [
                                'file' => $hook->name(),
                                'path' => $path,
                                'error' => "Method {$hookMethod->getName()} does not exist in {$hook->getClass()}",
                                'line' => $hookMethod->getStartLine()
                            ];
                        }
                    }
                }catch(\OutOfRangeException $e){
                    $warnings['errors'][] = [
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        return $warnings;
    }

    public static function extractLines(string $str, int $startLineInclusive, int $endLineInclusive): ?string {
        $startLine = $startLineInclusive - 1;
        $numLines = $endLineInclusive - $startLine;
        preg_match("/^(?:.*\n){{$startLine}}((?:.*\n){{$numLines}})/", $str, $matches);
        return $matches[1] ?? null;
    }

    protected function validateHookSignature(
        \ReflectionMethod $hookMethod,
        \ReflectionMethod $originalMethod,
        Hook $hook
    )
    {
        $path = $this->buildPath($hook->path(), $hookMethod->getStartLine());
        if ($originalMethod->isPrivate()) {
            return [
                'file' => $hook->name(),
                'path' => $path,
                'error' => "Method {$hookMethod->getName()} is private in {$originalMethod->getDeclaringClass()->getName()}",
                'line' => $hookMethod->getStartLine()
            ];
        }
        if ($originalMethod->isPublic() !== $hookMethod->isPublic()) {
            $originalModifiers = implode(' ', \Reflection::getModifierNames($originalMethod->getModifiers()));
            $hookModifiers = implode(' ', \Reflection::getModifierNames($hookMethod->getModifiers()));
            return [
                'file' => $hook->name(),
                'path' => $path,
                'error' => "Method {$hookMethod->getName()} ({$hookModifiers}) does not have same visibility as in " .
                    "{$originalMethod->getDeclaringClass()->getName()} ({$originalModifiers})",
                'line' => $hookMethod->getStartLine()
            ];
        }
        if ($originalMethod->isStatic() && !$hookMethod->isStatic()) {
            return [
                'file' => $hook->name(),
                'path' => $path,
                'error' => "Method {$hookMethod->getName()} is static in {$originalMethod->getDeclaringClass()->getName()}, " .
                    "but not in the hook",
                'line' => $hookMethod->getStartLine()
            ];
        }
        if (!$originalMethod->isStatic() && $hookMethod->isStatic()) {
            return [
                'file' => $hook->name(),
                'path' => $path,
                'error' => "{$hookMethod->getName()} is an instance method in " .
                    "{$originalMethod->getDeclaringClass()->getName()}, but static in the hook",
                'line' => $hookMethod->getStartLine()
            ];
        }
        if ($originalMethod->hasReturnType() && !$hookMethod->hasReturnType()) {
            return [
                'file' => $hook->name(),
                'path' => $path,
                'error' => "{$hookMethod->getName()} has a return type of {$originalMethod->getReturnType()->getName()} in " .
                    "{$originalMethod->getDeclaringClass()->getName()}, but no return type in the hook",
                'line' => $hookMethod->getStartLine()
            ];
        }

        return null;
    }

    protected function validateParameters(
        \ReflectionMethod $hookMethod,
        \ReflectionMethod $originalMethod,
        Hook $hook
    ){
        $checkRenames = !mb_stristr($hookMethod->getDocComment(), "@ips-lint no-check-renames");
        $zipped = array_map(null, $hookMethod->getParameters(), $originalMethod->getParameters());
        $path = $this->buildPath($hook->path(), $hookMethod->getStartLine());

        /** @var $param \ReflectionParameter[] */
        foreach ($zipped as $param) {
            if ($param[0] === null) {
                $extraParams = array_slice($originalMethod->getParameters(), $param[1]->getPosition());
                $paramNames = [];
                /** @var $extraParam \ReflectionParameter */
                foreach ($extraParams as $extraParam) {
                    $paramNames[] = $extraParam->getName();
                }
                $paramNamesString = implode(", ", $paramNames);
                return [
                    'file' => $hook->name(),
                    'path' => $path,
                    'error' => "Method {$originalMethod->getName()} is missing parameters {$paramNamesString} (defined in " .
                        "{$originalMethod->getDeclaringClass()->getName()})",
                    'line' => $hookMethod->getStartLine()
                ];
            }
            $method = "{$originalMethod->getDeclaringClass()->getName()}::{$originalMethod->getName()}";
            if (!$param[0]->isOptional()) {
                if ($param[1] === null) {

                    return [
                        'file' => $hook->name(),
                        'path' => $path,
                        'error' => "Parameter {$param[0]->getName()} does not exist in {$method}, but is required in the hook",
                        'line' => $hookMethod->getStartLine()
                    ];
                }
                if ($param[1]->isOptional()) {
                    return [
                        'file' => $hook->name(),
                        'path' => $path,
                        'error' => "Parameter {$param[0]->getName()} is optional in {$method}, but is required in the hook",
                        'line' => $hookMethod->getStartLine()
                    ];
                }
            } elseif ($param[1] !== null && $param[1]->isOptional()) {
                $hookDefault = $param[0]->getDefaultValue();
                $originalDefault = $param[1]->getDefaultValue();
                if ($hookDefault !== $originalDefault) {
                    return [
                        'file' => $hook->name(),
                        'path' => $path,
                        'error' => "Parameter {$param[0]->getName()} has default value " . print_r($originalDefault, true) .
                            " in {$method}, but " . print_r($hookDefault, true) . ' in the hook',
                        'line' => $hookMethod->getStartLine()
                    ];
                }
            }
            if ($param[0]->hasType() && !$param[1]->hasType()) {
                return [
                    'file' => $hook->name(),
                    'path' => $path,
                    'error' => "Parameter {$param[0]->getName()} is untyped in {$method}, but has type " .
                        "{$param[0]->getType()->getName()} in the hook",
                    'line' => $hookMethod->getStartLine()
                ];
            }
            if (
                $checkRenames &&
                $param[1] &&
                $param[0]->getName() !== $param[1]->getName() &&
                !in_array($param[0]->getName(), $this->conf['rename-ignored-names']) &&
                !in_array($param[1]->getName(), $this->conf['rename-ignored-names'])) {
                return [
                    'file' => $hook->name(),
                    'path' => $path,
                    'error' => "Hook parameter of {$param[0]->getName()} does not match original parameter of " .
                        "{$param[1]->getName()} declared in {$method}",
                    'line' => $hookMethod->getStartLine()
                ];
            }
        }
        return null;
    }

    protected function findParentUsages(Hook $hook, string $name, string $methodBody, int $firstLineNum): array {
        $lexer = new Lexer(['usedAttributes' => ['startLine']]);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
        try {
            $ast = $parser->parse("<?php class _fake_class_ {\n{$methodBody}\n}");
        } catch (\Exception $e) {
            throw new OutOfRangeException($e->getMessage().' Method: '. $name .' File: '.$hook->name());
        }

        $visitor = new ParentVisitor($firstLineNum - 1);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getParentCalls();
    }

    protected function identify(SplFileInfo $file){
        $baseName = $file->getBasename('.php');
        if(!isset($this->hookFile[$baseName])){
            throw new OutOfBoundsException('Hook filed, '.$file->getFilename().', doesn\'t exist');
        }
        $hook = $this->hookFile[$baseName];
        $this->existingHooks[$baseName] = new Hook($file, $hook);
    }
}