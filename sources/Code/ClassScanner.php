<?php

/**
* @brief      ClassScanner Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Code;

use Error;
use Exception;

use IPS\Data\Store;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\toolbox\Code\ParserAbstract;
use IPS\toolbox\Code\Utils\Hook;
use IPS\toolbox\Code\Utils\ParentVisitor;
use IPS\toolbox\Proxy\Proxyclass;
use OutOfRangeException;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* ClassScanner Class
* @mixin \IPS\toolbox\Code\ClassScanner
*/
class _ClassScanner extends ParserAbstract
{
    /**
     * paths should be relative to <app path>/sources/
     * @var array
     */
    protected array $excludedFolders = [
        'vendor',
        'Vendor',
        'ThirdParty',
        'Thirdparty',
        'thirdparty',
        '3rdParty',
        '3rdparty',
        'Composer',
        'composer'
    ];
    protected array $excludedFiles = [];
    /**
     * these are classes we stop before we get to the root parent.
     * @var array
     */
    protected array $fullStop = [
        'IPS\Content\_Comment' => 1,
        'IPS\Content\_Item' => 1,
        'IPS\Content\_Review' => 1,
        'IPS\Node\_Model' => 1,
    ];

    /**
     * these are methods inside some classes, that we don't need to check if they call the parent on, as
     * they are usually intended to be overloaded.
     * @var array|array[]
     */
    protected array $autoLint = [
        'IPS\Node\_Model' => [
            'getStore' => 1,
            'get__title' => 1,
            'formatFormValues' => 1,
            'form' => 1,
            'disabledPermissions' => 1,
            'titleFromIndexData' => 1
        ],
        'IPS\Helpers\_Form' => [
            '__construct' => 1,
            '__toString' => 1,
            'addButton' => 1,
            'customTemplate' => 1,
            'getLastUsedTab' => 1,
            'saveAsSettings' => 1,
            'values' => 1
        ],
        'IPS\Content\_Comment' => [
            'getStore' => 1,
            'contentTableTemplate' => 1,
            'titleFromIndexData' => 1
        ],
        'IPS\Content\_Item' => [
            'getStore' => 1,
            'form' => 1,
            'supportedMetaDataTypes' => 1,
            'contentTableTemplate' => 1,
            'titleFromIndexData' => 1
        ],
        'IPS\Content\_Review' => [
            'getStore' => 1,
            'titleFromIndexData' => 1
        ],
        'IPS\Helpers\Form\_FormAbstract' => [
            'formatValue' => 1,
            'getValue' => 1,
            'setValue' => 1
        ],
    ];
    protected function getFiles()
    {
        $files = new Finder();
        $files->in($this->getAppPath().'sources/')->name('*.php');
        if (empty($this->excludedFiles) === false) {
            $files->notName($this->excludedFiles);
        }
        if(empty($this->excludedFolders) === false){
            $files->exclude($this->excludedFolders);
        }
        $this->files = $files->files();
    }

    public function validate(): array
    {
        //we do this so we can capture the fatal and redirect if need be
        if(!Request::i()->isAjax()) {
            ob_start();
        }

        register_shutdown_function(function(){
            $error = error_get_last();
            $url = \IPS\Request::i()->url();
            if($error['type'] === E_COMPILE_ERROR){
                $url = $url->setQueryString(['do' => 'glitch'])->stripQueryString(['csrfKey','mr','download']);
                $url = (string) $url;
                Store::i()->toolbox_code_analyzer_interrupted = $error;
                if(Request::i()->isAjax()){
                    Output::i()->json( array(
                        'redirect' => (string) $url,
                        'message' => ''
                    )
                    );
                }
                else {
                    header("Location: {$url}");
                }
            }
        });
        $warnings = [
            'processing' => [],
            'signature' => [],
            'parameters' => [],
            'parentUsage' => [],
            'case' => [],
            'errors' => [],
        ];
        /** @var SplFileInfo $file */
        foreach($this->files as $file){
            $content = $file->getContents();
            $tokens = Proxyclass::i()->tokenize($content);
            try {
                if (empty($tokens) === true || $tokens['type'] === T_TRAIT || $tokens['type'] === T_INTERFACE) {
                    continue;
                }
            }catch( Throwable $e){
            }
            $cs = $tokens['class'];
            $ns = $tokens['namespace'];
            $filename = $file->getFilenameWithoutExtension();

            //lets make sure this is an IPS class!
            if(str_starts_with($cs, '_') === true && str_contains($ns, 'IPS') === true) {
                $first = mb_substr($cs, 1);
                $className = '\\' . $tokens['namespace'] . '\\' . $first;
                if($filename !== $first){
                    $currentFileName = str_replace($this->app->getApplicationPath(),'',$file->getRealPath());
                    $warnings['case'][] = [
                        'error' => 'Case Mismatch',
                        'path' => [
                            'url' => $this->buildPath($file->getRealPath(), 0),
                            'name' => $currentFileName
                        ],
                        'class' => $first
                    ];
                }
                //check for case-insensitive/preserving. this wouldn't have been a problem normally, but just recently
                //found out that ext4 in new versions of the linux kernel support "case folding" which is case-preserving
                //i'm gonna say this is MS's influence on the kernel!

                try {
                    $currentClass = new \ReflectionClass($className);
                    $currentClass = $currentClass->getParentClass();
                    //okay this is not a class we are gonna check, as its not a child/subclass
                    if($currentClass->getParentClass() === false){
                        continue;
                    }
                    //so we are here, first things first, we have to get the original parent class, we will have to make
                    //a few exceptions here, like if it is an item or node, as they both extend AR
                    //due to IPS monkey patching, we have to traverse till we get to the original parent to check for changes
                    // otherwise we will just be comparing the hook to the hook...
                    $done = false;
                    $pc = $currentClass;
                    while ($done !== true){
                        $parentClass = $pc->getParentClass();
                        $pc = $parentClass;
                        if ($parentClass instanceof ReflectionClass) {
                            $name = $parentClass->getName();
                            if(!str_contains($name,'IPS')){
                                $done = true;
                                //if this is not an IPS class, we need to vamoose
                                continue 2;
                            }

                            //is this one of those classes we don't want to go all the back on?
                            if(isset($this->fullStop[$name]) || $pc->getParentClass() === false){
                                $done = true;
                            }
                        } else {
                            $done = true;
                        }
                    }
                    foreach($currentClass->getTraits() as $trait){
                        $contentTrait = \file_get_contents($trait->getFileName());
                        $this->validationChecks( $trait, $parentClass, $contentTrait, $warnings);

                    }
                    $this->validationChecks( $currentClass, $parentClass, $content, $warnings);
                } catch (Throwable | Exception | Error $e) {
                    $path = $this->buildPath($file->getRealPath(),$e->getLine());
                    $warnings['processing'][] = [
                        'error' => $e->getMessage(),
                        'path' => ['url' => $path, 'name' => $file->getFilename()],
                    ];
                    continue;
                }
            }
        }
        return $warnings;
    }

    protected function validationChecks(
        ReflectionClass $currentClass,
        Reflectionclass $parentClass,
        string $content,
        &$warnings
    ){
        //now lets get that money shot!
        foreach ($currentClass->getMethods() as $method) {
//                        if($method->getName() === 'parents'){
//                            _p($cn, $method->getDeclaringClass()->getName());
//                        }
            if ($currentClass->getName() === $method->getDeclaringClass()->getName()) {
                //okay php is a bit moronic at times, trait methods that override parentclass methods,
                //show up as apart of the class being check, but there is no "real way" to check
                //if the method is from a trait/current class, so we are gonna get a bit dirty here
                //who doesn't like getting a bit dirty?
                //if this fails, it is likely a trait method and i'm not entirely sure how to handle them...
                //or should i handle them? yes...i'll handle them later
                //@todo implement for traits
                if($method->getFileName() !== $currentClass->getFileName()){
                    continue;
                }
//                            if($method->getName() === 'parents'){
//                                _p(
//                                    $method->getDeclaringClass()->isTrait(),
//                                    $method->getDeclaringClass()->getName(),
//                                    $method->isVariadic(),
//                                    $method->getFileName(),
//                                    $currentClass->getFileName(),
//                                    $currentClass->hasMethod($method->getName())
//                                );
//                            }
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
                        $originalMethod = $parentClass->getMethod($method->getName());
                    } catch (Throwable $e) {
                        continue;
                    }
                    if(!str_contains($docComment, '@ips-lint ignore-signature')) {
                        $this->validateSignature(
                            $method,
                            $originalMethod,
                            $warnings
                        );
                    }

                    if(!str_contains($docComment, '@ips-lint ignore-parameters')) {
                        $this->validateParameters(
                            $method,
                            $originalMethod,
                            $warnings
                        );
                    }

                    if(!str_contains($docComment, '@ips-lint ignore-parent')) {
                        try {
                            try {
                                //let's see if the methods that exist in the parent class, are getting called here!
                                $parentUsages = $this->findParentUsages($method, $content);
                            } catch (\OutOfRangeException $e) {
                                $parentUsages = [];
                            }
                            $methodName = \mb_strtolower($method->getName());
                            if (!isset($parentUsages[$methodName])) {
                                $path = $this->buildPath($currentClass->getFileName(), $method->getStartLine());
                                $warnings['parentUsage'][] = [
                                    'error' => "Does not call parent",
                                    'path' => ['url' => $path, 'name' => $currentClass->getFileName()],
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
                            'url' => $this->buildPath($currentClass->getFileName(), 0),
                            'name' => $currentClass->getFileName()
                        ],
                        'line' => $e->getLine(),
                        'method' => $method->getName()
                    ];
                }
            }
        }
    }
    protected function findParentUsages(\ReflectionMethod $method, string $content): array {
        $methodBody = Hooks::extractLines(
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
        } catch (\Exception $e) {
            throw new OutOfRangeException($e->getMessage().' Method: '. $name .' File: '.$method->getDeclaringClass()->getFileName());
        }
        $visitor = new ParentVisitor($firstLineNum-1);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getParentCalls();
    }

    protected function validateParameters(
        \ReflectionMethod $currentMethod,
        \ReflectionMethod $originalMethod,
        &$warnings
    )
    {
        $methodName = $currentMethod->getName();
        $currentMethodStartLine = $currentMethod->getStartLine();
        $currentFileName = $currentMethod->getDeclaringClass()->getFileName();
        //here we are building an editor path, so if you are using an editors protocol like phpstorm has available
        $path = $this->buildPath(
            $currentFileName,
            $currentMethodStartLine
        );
        $currentFileName = str_replace($this->app->getApplicationPath(),'',$currentFileName);
        $zipped = array_map(null, $currentMethod->getParameters(), $originalMethod->getParameters());

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
                    'error' => "Missing Parameter: {$paramNamesString}",
                    'path' => ['url' => $path, 'name' => $currentFileName],
                    'line' => $currentMethodStartLine,
                    'method' => $methodName
                ];
            }
            $method = "{$originalMethod->getDeclaringClass()->getName()}::{$originalMethod->getName()}";
            if (isset($param[0]) && !$param[0]->isOptional()) {
                if (isset($param[1]) && $param[1] === null) {

                    $warnings['parameters'][] = [
                        'error' => "Parameter \${$param[0]->getName()} is required but missing.",
                        'path' => ['url' => $path, 'name' => $currentFileName],
                        'line' => $currentMethodStartLine,
                        'method' => $methodName
                    ];
                }
                if (isset($param[1]) && $param[1]->isOptional()) {
                    $warnings['parameters'][] = [
                        'error' => "Parameter \${$param[0]->getName()} is required but set as optional in child",
                        'path' => ['url' => $path, 'name' => $currentFileName],
                        'line' => $currentMethodStartLine,
                        'method' => $methodName
                    ];
                }
            } elseif (isset($param[1]) && $param[1] !== null && $param[1]->isOptional()) {
                $hookDefault = $param[0]->getDefaultValue();
                $originalDefault = $param[1]->getDefaultValue();
                if ($hookDefault !== $originalDefault) {
                    $warnings['parameters'][] = [
                        'error' => "Parameter \${$param[0]->getName()} mismatched default value.",
                        'path' => ['url' => $path, 'name' => $currentFileName],
                        'line' => $currentMethodStartLine,
                        'method' => $methodName
                    ];
                }
            }
            if (isset($param[0]) && $param[0]->hasType() && $param[1] && !$param[1]->hasType()) {
                $warnings['parameters'][] = [
                    'error' => "Parameter \${$param[0]->getName()} parameter type/hint mismatch.",
                    'path' => ['url' => $path, 'name' => $currentFileName],
                    'line' => $currentMethodStartLine,
                    'method' => $methodName
                ];
            }
            if (
                isset($param[0]) &&
                isset($param[1]) &&
                $param[1] &&
                $param[0]->getName() !== $param[1]->getName()
            ) {
                $warnings['parameters'][] = [
                    'error' => "Parameter of \${$param[0]->getName()} name mismatch.",
                    'path' => ['url' => $path, 'name' => $currentFileName],
                    'line' => $currentMethodStartLine,
                    'method' => $methodName
                ];
            }
        }
    }

    public function validateSignature(
        \ReflectionMethod $currentMethod,
        \ReflectionMethod $originalMethod,
        &$warnings
    )
    {
        $methodName = $currentMethod->getName();
        $currentMethodStartLine = $currentMethod->getStartLine();
        $currentFileName = $currentMethod->getDeclaringClass()->getFileName();
        //here we are building an editor path, so if you are using an editors protocol like phpstorm has available
        $path = $this->buildPath(
            $currentFileName,
            $currentMethodStartLine
        );
        $currentFileName = str_replace($this->app->getApplicationPath(),'',$currentFileName);

        //this might not be needed, but check if the parent is private
        if ($originalMethod->isPrivate()) {
            $warnings['signature'][] = [
                'error' => "Method's visibility in parent is private.",
                'path' => ['url' => $path, 'name' => $currentFileName],
                'line' => $currentMethodStartLine,
                'method' => $methodName,
            ];
        }

        //check if they have switched visibility
        if (
            ($originalMethod->isPublic() !== $currentMethod->isPublic() ) ||
            ($originalMethod->isProtected() !== $currentMethod->isProtected())
        ) {
            $originalModifiers = implode(' ', \Reflection::getModifierNames($originalMethod->getModifiers()));
            $currentModifiers = implode(' ', \Reflection::getModifierNames($currentMethod->getModifiers()));
            $warnings['signature'][] = [
                'error' => "Method's visibility mismatch.",
                'path' => ['url' => $path, 'name' => $currentFileName],
                'line' => $currentMethodStartLine,
                'method' => $methodName,
            ];
        }

        //lets see if they changed it from a static method to a instance method
        if ($originalMethod->isStatic() && !$currentMethod->isStatic()) {
            $warnings['signature'][] = [
                'error' => "Method should be static",
                'path' => ['url' => $path, 'name' => $currentFileName],
                'line' => $currentMethodStartLine,
                'method' => $methodName,
            ];
        }
        if (!$originalMethod->isStatic() && $currentMethod->isStatic()) {
            $warnings['signature'][] = [
                'error' => "Method should not be static",
                'path' => ['url' => $path, 'name' => $currentFileName],
                'line' => $currentMethodStartLine,
                'method' => $methodName
            ];
        }
        if ($originalMethod->hasReturnType() && !$currentMethod->hasReturnType()) {
            $warnings['signature'][] = [
                'error' => "Method is missing return type.",
                'path' => ['url' => $path, 'name' => $currentFileName],
                'line' => $currentMethodStartLine,
                'method' => $methodName
            ];
        }

        if (!$originalMethod->hasReturnType() && $currentMethod->hasReturnType()) {
            $warnings['signature'][] = [
                'error' => "Method return type mismatch.",
                'path' => ['url' => $path, 'name' => $currentFileName],
                'line' => $currentMethodStartLine,
                'method' => $methodName
            ];
        }
    }
}
