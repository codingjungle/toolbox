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

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Request;
use IPS\toolbox\Application;
use IPS\toolbox\Code\ParserAbstract;
use IPS\toolbox\Code\Utils\Hook;
use IPS\toolbox\Code\Utils\HookClass;
use IPS\toolbox\Code\Utils\ParentVisitor;
use OutOfBoundsException;
use OutOfRangeException;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Throwable;

use toolbox_IPS_Plugin_Hook_ab9712a0d65901062b22f5262a724bd72\_HOOK_CLASS_;

use function _d;
use function _p;
use function array_pop;
use function class_exists;
use function defined;
use function explode;
use function file_exists;
use function header;
use function print_r;
use function array_slice;

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
    protected const IGNORED_PARENTS = [
        'manage'
    ];
    protected $hookFile;
    protected $existingHooks = [];
    protected $conf;

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
            $name = $file->getBasename();

            try {
                $this->identify($file);
            }
            catch(\OutOfBoundsException $e){
                $warnings[$name] = [
                    'file' => $name,
                    'editorPath' => $this->buildPath($file->getRealPath(),0),
                    'path' => $file->getRealPath(),
                    'error' => $e->getMessage(),
                ];
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
            'errors' => [],
        ];

        /** @var Hook $hook */
        foreach($this->existingHooks as $hook) {
            //okay we don't want any of those dirty templates yet!
            if ($hook->isThemHook() === true) {
                continue;
            }

            $newName = uniqid('hook_', false);
//            $content = \file_get_contents(Application::getRootPath('core').'/foo.php');

            //rename it and load it up to a empty class so we don't confuse things here
            $content = preg_replace(
                '/class \S+ extends _HOOK_CLASS_/',
                "class {$newName} extends \\IPS\\toolbox\\Code\\Utils\\HookClass",
               $hook->getContent()
            );

            //eval the content, will throw an error if stuff is right in it, like junk characters or something
            try {
                @eval($content);
            } catch (Throwable | \ParseError $e) {
                $path = $this->buildPath($hook->path(),$e->getLine());

                $warnings['parse'][] = [
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ];
                continue;
            }

            //this shouldn't throw an error, but you never know!
            try {
                $hookClass = new \ReflectionClass($newName);
            } catch (Throwable | \Exception $e) {
                $path = $this->buildPath($hook->path(),$e->getLine());
                $warnings['processing'][] = [
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ];
                continue;
            }

            //do we want this hook to be ignored?
            if (mb_stristr($hookClass->getDocComment(), '@ips-lint ignore')) {
                return [];
            }

            //now load the original class, this should be fun
            try {
                $originalClass = new \ReflectionClass($hook->getClass());
            } catch (\ReflectionException $e) {
                $path = $this->buildPath($hook->path(),$e->getLine());
                $warnings['parent'][] = [
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ];
                continue;
            }

            //due to IPS monkey patching, we have to traverse till we get to the original parent to check for changes
            // otherwise we will just be comparing the hook to the hook...
            if($originalClass->getParentClass()) {
                $hookedClass = explode("\\", $hook->getClass());
                $hookedClass = '_'.array_pop($hookedClass);
                $done = false;
               while ($done !== true){
                   $parent = $originalClass->getParentClass();
                   if ($parent instanceof ReflectionClass) {
                       $parentClass = explode("\\", $parent->getName());
                       $parentClass = array_pop($parentClass);
                       $originalClass = $parent;
                       if( $parentClass === $hookedClass) {
                           $done = true;
                       }
                   } else {
                       $done = true;
                   }
               }
            }

            //now lets get that money shot!
            foreach ($hookClass->getMethods() as $hookMethod) {
                if (!mb_stristr($hookMethod->getDocComment(), '@ips-lint ignore')) {
                    try {
                        //we are only interested in parent extend classes here
                        $originalMethod = $originalClass->getMethod($hookMethod->getName());
                        $this->validateHookSignature(
                            $hookMethod,
                            $originalMethod,
                            $hook,
                            $originalClass->getFileName(),
                            $warnings
                        );
                        $this->validateParameters(
                            $hookMethod,
                            $originalMethod,
                            $hook,
                            $originalClass->getFileName(),
                            $warnings
                        );

                        $methodBody = static::extractLines(
                            $content,
                            $hookMethod->getStartLine(),
                            $hookMethod->getEndLine()
                        );
                        try {
                            if(!\in_array($hookMethod->getName(),static::IGNORED_PARENTS)) {
                                //let's see if the methods that exist in the parent class, are getting called here!
                                $parentUsages = $this->findParentUsages(
                                    $hook,
                                    $hookMethod->getName(),
                                    $methodBody,
                                    $hookMethod->getStartLine()
                                );
                                $methodName = \mb_strtolower($hookMethod->getName());
                                if (!isset($parentUsages[$methodName])) {
                                    $path = $this->buildPath($hook->path(), $hookMethod->getStartLine());
                                    $warnings['parentUsage'][] = [
                                        'path' => ['url' => $path, 'name' => $hook->path()],
                                        'error' => "Method {$hookMethod->getName()} does not exist in {$hook->getClass()}",
                                        'line' => $hookMethod->getStartLine()
                                    ];
                                }
                            }
                        } catch (\OutOfRangeException $e) {
                            $warnings['errors'][] = [
                                'path' => ['url'=>$this->build($hook->path(),0), 'name' => $hook->path()],
                                'error' => $e->getMessage(),
                                'line' => 0
                            ];
                        }
                    } catch (\ReflectionException $e) {
                    }
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
        Hook $hook,
        string $originalFilePath,
        &$warnings
    )
    {
        $path = $this->buildPath($hook->path(), $hookMethod->getStartLine());
        $path2 = $this->buildPath($originalFilePath, $originalMethod->getStartLine());
        if ($originalMethod->isPrivate()) {
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path, 'name' => $hook->path()],
                'error' => "Method {$hookMethod->getName()} is private in {$originalMethod->getDeclaringClass()->getName()}",
                'line' => $hookMethod->getStartLine()
            ];
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path2, 'name' => $originalFilePath],
                'error' => "Method {$hookMethod->getName()} does not exist in {$hook->getClass()}",
                'line' => $originalMethod->getStartLine()
            ];
        }
        if ($originalMethod->isPublic() !== $hookMethod->isPublic()) {
            $originalModifiers = implode(' ', \Reflection::getModifierNames($originalMethod->getModifiers()));
            $hookModifiers = implode(' ', \Reflection::getModifierNames($hookMethod->getModifiers()));
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path, 'name' => $hook->path()],
                'error' => "Method {$hookMethod->getName()} ({$hookModifiers}) does not have same visibility as in " .
                    "{$originalMethod->getDeclaringClass()->getName()} ({$originalModifiers})",
                'line' => $hookMethod->getStartLine()
            ];
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path2, 'name' => $originalFilePath],
                'error' => "Method {$hookMethod->getName()} ({$hookModifiers}) does not have same visibility as in " .
                    "{$originalMethod->getDeclaringClass()->getName()} ({$originalModifiers})",
                'line' => $originalMethod->getStartLine()
            ];
        }
        if ($originalMethod->isStatic() && !$hookMethod->isStatic()) {

            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path, 'name' => $hook->path()],
                'error' => "Method {$hookMethod->getName()} is static in {$originalMethod->getDeclaringClass()->getName()}, " .
                    "but not in the hook",

                'line' => $hookMethod->getStartLine()
            ];
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path2, 'name' => $originalFilePath],
                'error' => "Method {$hookMethod->getName()} is static in {$originalMethod->getDeclaringClass()->getName()}, " .
                    "but not in the hook",

                'line' => $originalMethod->getStartLine()
            ];
        }
        if (!$originalMethod->isStatic() && $hookMethod->isStatic()) {

            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path, 'name' => $hook->path()],
                'error' => "{$hookMethod->getName()} is an instance method in " . "{$originalMethod->getDeclaringClass()->getName()}, but static in the hook",
                'line' => $hookMethod->getStartLine()
            ];
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path2, 'name' => $originalFilePath],
                'error' => "{$hookMethod->getName()} is an instance method in " . "{$originalMethod->getDeclaringClass()->getName()}, but static in the hook",
                'line' => $originalMethod->getStartLine()
            ];
        }
        if ($originalMethod->hasReturnType() && !$hookMethod->hasReturnType()) {
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path, 'name' => $hook->path()],
                'error' => "{$hookMethod->getName()} has a return type of {$originalMethod->getReturnType()->getName()} in " . "{$originalMethod->getDeclaringClass()->getName()}, but no return type in the hook",
                'line' => $hookMethod->getStartLine()
            ];
            $warnings['signature'][] = [
                'hook' => $hook->name(),
                'path' => ['url' => $path2, 'name' => $originalFilePath],
                'error' => "{$hookMethod->getName()} has a return type of {$originalMethod->getReturnType()->getName()} in " . "{$originalMethod->getDeclaringClass()->getName()}, but no return type in the hook",
                'line' => $originalMethod->getStartLine()
            ];
        }
    }

    protected function validateParameters(
        \ReflectionMethod $hookMethod,
        \ReflectionMethod $originalMethod,
        Hook $hook,
        string $originalFilePath,
        &$warnings
    ){
        $checkRenames = !mb_stristr($hookMethod->getDocComment(), "@ips-lint no-check-renames");
        $zipped = array_map(null, $hookMethod->getParameters(), $originalMethod->getParameters());
        $path = $this->buildPath($hook->path(), $hookMethod->getStartLine());
        $path2 = $this->buildPath($originalFilePath, $originalMethod->getStartLine());
        /** @var $param \ReflectionParameter[] */
        foreach ($zipped as $param) {
            if ( $param[0] === null) {
                $extraParams = array_slice($originalMethod->getParameters(), $param[1]->getPosition());
                $paramNames = [];
                /** @var $extraParam \ReflectionParameter */
                foreach ($extraParams as $extraParam) {
                    $paramNames[] = $extraParam->getName();
                }
                $paramNamesString = implode(", ", $paramNames);

                $warnings['parameters'][] = [
                    'hook' => $hook->name(),
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'error' => "Method {$originalMethod->getName()} is missing parameters {$paramNamesString} (defined in " . "{$originalMethod->getDeclaringClass()->getName()})",
                    'line' => $hookMethod->getStartLine()
                ];
                $warnings['parameters'][] = [
                    'hook' => $hook->name(),
                    'path' => ['url' => $path2, 'name' => $originalFilePath],
                    'error' => "Method {$originalMethod->getName()} is missing parameters {$paramNamesString} (defined in " . "{$originalMethod->getDeclaringClass()->getName()})",
                    'line' => $originalMethod->getStartLine()
                ];
            }
            $method = "{$originalMethod->getDeclaringClass()->getName()}::{$originalMethod->getName()}";
            if (isset($param[0]) && !$param[0]->isOptional()) {
                if (isset($param[1]) && $param[1] === null) {

                    $warnings['parameters'][] = [
                        'hook' => $hook->name(),
                        'path' => ['url' => $path, 'name' => $hook->path()],
                        'error' => "Parameter {$param[0]->getName()} does not exist in {$method}, but is required in the hook",
                        'line' => $hookMethod->getStartLine()
                    ];
                    $warnings['parameters'][] = [
                        'hook' => $hook->name(),
                        'path' => ['url' => $path2, 'name' => $originalFilePath],
                        'error' => "Parameter {$param[0]->getName()} does not exist in {$method}, but is required in the hook",
                        'line' => $originalMethod->getStartLine()
                    ];
                }
                if (isset($param[1]) && $param[1]->isOptional()) {
                    $warnings['parameters'][] = [
                        'hook' => $hook->name(),
                        'path' => ['url' => $path, 'name' => $hook->path()],
                        'error' => "Parameter {$param[0]->getName()} is optional in {$method}, but is required in the hook",
                        'line' => $hookMethod->getStartLine()
                    ];
                    $warnings['parameters'][] = [
                        'hook' => $hook->name(),
                        'path' => ['url' => $path2, 'name' => $originalFilePath],
                        'error' => "Parameter {$param[0]->getName()} is optional in {$method}, but is required in the hook",
                        'line' => $originalMethod->getStartLine()
                    ];
                }
            } elseif (isset($param[1]) && $param[1] !== null && $param[1]->isOptional()) {
                $hookDefault = $param[0]->getDefaultValue();
                $originalDefault = $param[1]->getDefaultValue();
                if ($hookDefault !== $originalDefault) {
                    $warnings['parameters'][] = [
                        'hook' => $hook->name(),
                        'path' => ['url' => $path, 'name' => $hook->path()],
                        'error' => "Parameter {$param[0]->getName()} has default value " . print_r($originalDefault, true) . " in {$method}, but " . print_r($hookDefault, true) . ' in the hook',
                        'line' => $hookMethod->getStartLine()
                    ];
                    $warnings['parameters'][] = [
                        'hook' => $hook->name(),
                        'path' => ['url' => $path2, 'name' => $originalFilePath],
                        'error' => "Parameter {$param[0]->getName()} has default value " . print_r($originalDefault, true) . " in {$method}, but " . print_r($hookDefault, true) . ' in the hook',
                        'line' => $originalMethod->getStartLine()
                    ];
                }
            }
            if (isset($param[0]) && $param[0]->hasType() && !$param[1]->hasType()) {
                $warnings['parameters'][] = [
                    'hook' => $hook->name(),
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'error' => "Parameter {$param[0]->getName()} is untyped in {$method}, but has type " . "{$param[0]->getType()->getName()} in the hook",
                    'line' => $hookMethod->getStartLine()
                ];
                $warnings['parameters'][] = [
                    'hook' => $hook->name(),
                    'path' => ['url' => $path2, 'name' => $originalFilePath],
                    'error' => "Parameter {$param[0]->getName()} is untyped in {$method}, but has type " . "{$param[0]->getType()->getName()} in the hook",
                    'line' => $originalMethod->getStartLine()
                ];
            }
            if (
                isset($param[0]) &&
                isset($param[1]) &&
                $param[1] &&
                $param[0]->getName() !== $param[1]->getName()
            ) {
                $warnings['parameters'][] = [
                    'hook' => $hook->name(),
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'error' => "Hook parameter of {$param[0]->getName()} does not match original parameter of " . "{$param[1]->getName()} declared in {$method}",
                    'line' => $hookMethod->getStartLine()
                ];
                $warnings['parameters'][] = [
                    'hook' => $hook->name(),
                    'path' => ['url' => $path2, 'name' => $originalFilePath],
                    'error' => "Hook parameter of {$param[0]->getName()} does not match original parameter of " . "{$param[1]->getName()} declared in {$method}",
                    'line' => $originalMethod->getStartLine()
                ];
            }
        }
    }

    protected function findParentUsages(Hook $hook, string $name, ?string $methodBody, int $firstLineNum): array {
        $lexer = new Lexer(['usedAttributes' => ['startLine']]);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
        try {
            $ast = $parser->parse("<?php class _fake_class_ {\n{$methodBody}\n}");
        } catch (\Exception $e) {
            throw new OutOfRangeException($e->getMessage().' Method: '. $name .' File: '.$hook->name());
        }
        $visitor = new ParentVisitor($firstLineNum-1);
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