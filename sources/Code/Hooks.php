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
    use Scanner;

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
            catch(OutOfBoundsException $e){
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
        if(empty($this->existingHooks) === true && empty($this->files) === false){
            $this->exist();
        }
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
                    'error' => $e->getMessage(),
                    'path' => ['url' => $path, 'name' => $hook->path()],
                    'line' => $e->getLine()
                ];
                continue;
            }

            //this shouldn't throw an error, but you never know!
            try {
                $hookClass = new ReflectionClass($newName);
            } catch (Throwable |Exception $e) {
                $path = $this->buildPath($hook->path(),$e->getLine());
                $warnings['processing'][] = [
                    'error' => $e->getMessage(),
                    'path' => ['url' => $path, 'name' => $hook->path()],
                ];
                continue;
            }

            //do we want this hook to be ignored?
            if (mb_stristr($hookClass->getDocComment(), '@ips-lint ignore')) {
                return [];
            }

            //now load the original class, this should be fun
            try {
                $originalClass = new ReflectionClass($hook->getClass());
            } catch (ReflectionException $e) {
                $path = $this->buildPath($hook->path(),$e->getLine());
                $warnings['parent'][] = [
                    'error' => $e->getMessage(),
                    'path' => ['url' => $path, 'name' => $hook->path()],
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
                       $name = $parent->getName();
                       if (!str_contains($name, 'IPS')) {
                           $done = true;
                           //if this is not an IPS class, we need to vamoose
                           continue 2;
                       }
                       $parentClass = explode("\\", $parent->getName());
                       $parentClass = array_pop($parentClass);
                       $originalClass = $parent;
                       if(isset($this->fullStop[$name]) || $parentClass === $hookedClass) {
                           $done = true;
                       }
                   } else {
                       $done = true;
                   }
               }
            }
            //now lets get that money shot!
            $this->validationChecks($hookClass, $originalClass, $content, $hook->path(), true, $warnings);
        }
        return $warnings;
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
