<?php

/**
 * @brief       Proxyclass Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Proxy Class Generator
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Proxy;

use Exception;
use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Output\_System;
use IPS\Patterns\Singleton;
use IPS\Settings;
use IPS\Theme;
use IPS\toolbox\Application;
use IPS\toolbox\Generator\DTFileGenerator;
use IPS\toolbox\Profiler\Debug;
use IPS\toolbox\Proxy\Generator\Applications;
use IPS\toolbox\Proxy\Generator\Db as GeneratorDb;
use IPS\toolbox\Proxy\Generator\Extensions;
use IPS\toolbox\Proxy\Generator\Language;
use IPS\toolbox\Proxy\Generator\Moderators;
use IPS\toolbox\Proxy\Generator\Proxy;
use IPS\toolbox\Proxy\Generator\Templates;
use IPS\toolbox\Proxy\Generator\Url;
use IPS\toolbox\Proxy\Generator\Writer;
use IPS\toolbox\Shared\Providers;
use IPS\toolbox\Shared\Read;
use IPS\toolbox\Shared\Replace;
use IPS\toolbox\Shared\Write;
use OutOfRangeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;
use Laminas\Code\Generator\ClassGenerator;

use function array_keys;
use function array_merge;
use function asort;
use function chmod;
use function count;
use function defined;
use function fclose;
use function fgets;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function fwrite;
use function header;
use function in_array;
use function is_array;
use function is_dir;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function md5;
use function mkdir;
use function mt_rand;
use function preg_match;
use function preg_replace_callback;
use function str_replace;
use function time;
use function token_get_all;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;
use const T_ABSTRACT;
use const T_CLASS;
use const T_FINAL;
use const T_INTERFACE;
use const T_NS_SEPARATOR;
use const T_STRING;
use const T_TRAIT;
use const T_WHITESPACE;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _Proxyclass
 *
 * @package IPS\toolbox\Proxy
 */
class _Proxyclass extends Singleton
{

    use Read;
    use Replace;
    use Write;

    /**
     * @inheritdoc
     */
    protected static $instance;

    /**
     * stder resource
     *
     * @var
     */
    protected static $fe;

    /**
     * the save location for the proxyclasses
     *
     * @var string
     */
    public $save = 'dtProxy';

    /**
     * build the proxy properties
     *
     * @var bool
     */
    public $doProps = true;

    /**
     * build the header info
     *
     * @var bool
     */
    public $doHeader = true;

    /**
     * build the proxy constants
     *
     * @var bool
     */
    public $doConstants = true;

    /**
     * builds the metadata
     *
     * @var bool
     */
    public $doProxies = true;

    /**
     * stores templates data
     *
     * @var array
     */
    public $templates = [];

    /**
     * @var bool
     */
    public $console = false;

    /**
     * @var string
     */
    protected $meta = '';

    /**
     * _Proxyclass constructor.
     *
     * @param bool $console
     */
    public function __construct(bool $console = null)
    {
        $this->console = $console ?? false;

        Application::loadAutoLoader();
        $this->blanks = \IPS\Application::getRootPath() . '/applications/toolbox/data/defaults/';
        if (!Settings::i()->dtproxy_do_props) {
            $this->doProps = false;
        }

        if (!Settings::i()->dtproxy_do_constants) {
            $this->doConstants = false;
        }

        if (!Settings::i()->dtproxy_do_proxies) {
            $this->doProxies = false;
        }
        $this->save = \IPS\Application::getRootPath() . '/' . $this->save;
    }

    /**
     * this is used for the controller and the MR
     *
     * @param array $data
     *
     * @return array|null
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     * @deprecated
     * @todo this should be removed as it is no longer used
     */
    public function run(array $data = [])
    {
//        $i = 0;
//        $totalFiles = 0;
//        $iterator = 0;
//        if (isset(Store::i()->dtproxy_proxy_files)) {
//            if (isset(Store::i()->dtproxy_templates)) {
//                $this->templates = Store::i()->dtproxy_templates;
//            }
//
//            /**
//             * @var $iterator array
//             */
//            $iterator = Store::i()->dtproxy_proxy_files;
//            $totalFiles = $data['total'] ?? 0;
//            $limit = 1;
//            if (!isset($data['firstRun'])) {
//                $limit = 250;
//            }
//
//            foreach ($iterator as $key => $file) {
//                $i++;
//                $filePath = $file;
//                $this->build($filePath);
//                unset($iterator[$key]);
//                if ($i === $limit) {
//                    break;
//                }
//            }
//
//            unset(Store::i()->dtproxy_proxy_files);
//        }
//
//        if ($i) {
//            if (is_array($iterator) && count($iterator)) {
//                Store::i()->dtproxy_proxy_files = $iterator;
//            }
//
//            if (is_array($this->templates) && count($this->templates)) {
//                Store::i()->dtproxy_templates = $this->templates;
//            }
//
//            if (isset($data['current']) && $data['current']) {
//                $offset = $data['current'] + $i;
//            } else {
//                $offset = $i;
//            }
//
//            return ['total' => $totalFiles, 'current' => $offset, 'progress' => $data['progress'] ?? 0];
//        }
//
//        /**
//         * @todo this is ugly, we should improve this!
//         */
//        $steps = 0;
//        $step = $data['step'] ?? null;
//        $lastStep = $step;
//        $complete = $data['complete'] ?? 0;
//        if ($this->doConstants) {
//            if ($step === null) {
//                $step = 'constants';
//            }
//            $steps++;
//        }
//
//        /**
//         * @todo this will get annoying sooner or later, should find a better way to handle the "totals"
//         */
//        if ($this->doProxies) {
//            $steps += 7;
//        } elseif ($step === 'apps') {
//            $step = null;
//        }
//
//        if ($step === 'constants') {
//            Proxy::i()->buildConstants();
//            $complete++;
//            $lastStep = $step;
//            $step = 'apps';
//
//            return ['step' => $step, 'lastStep' => $lastStep, 'tot' => $steps, 'complete' => $complete];
//        }
//
//        if ($this->doProxies) {
//            $step = $this->makeToolboxMeta($step);
//            $complete++;
//        } else {
//            $step = null;
//        }
//
//        if ($step === null) {
////            (new GitHooks(\IPS\Application::applications()))->writeSpecialHooks();
//            Proxy::i()->generateSettings();
//            $this->buildCss();
//            unset(Store::i()->dtproxy_proxy_files, Store::i()->dtproxy_templates);
//            return null;
//        }
//
//        return ['step' => $step, 'lastStep' => $lastStep, 'tot' => $steps, 'complete' => $complete];
    }

    /**
     * this will take a file path, and create proxy classes from it
     *
     * @param $file
     */
    public function build($file)
    {

        try {
            $finder = new SplFileInfo($file);
            $this->compareMd5($file);
            $content = $this->_getFileByFullPath($file);
            $templates = [];
            $templates = Store::i()->dtproxy_templates ?? [];
            if ($finder->getExtension() === 'phtml') {
                $methodName = $finder->getBasename('.' . $finder->getExtension());
                preg_match('/^<ips:template parameters="(.+?)?"(.+?)?\/>(\r\n?|\n)/', $content, $params);

                if (isset($params[0])) {
                    $parameters = null;
                    if (isset($params[1])) {
                        $parameters = $params[1];
                    }

                    $templates[$file] = [
                        'method' => $methodName,
                        'params' => $parameters
                    ];
                }
                Store::i()->dtproxy_templates = $templates;
            } elseif ($finder->getExtension() === 'php') {
                Proxy::i()->create($content, $file);
            }
        }
        catch(PassedChecksum $e){

        }
    }

    public function compareMd5(string $file){
        try {
            $md5Files = Store::i()->dtproxy_md5;
        }catch(\OutOfRangeException $e){
            $md5Files = [];
        }

        if(isset($md5Files[$file])){
            $hash = md5_file($file);
            if($hash === $md5Files[$file]){
                throw new PassedChecksum();
            }
            else{
                $md5Files[$file] = $hash;
            }
        }
        else{
            $hash = md5_file($file);
            $md5Files[$file] = $hash;
        }
        Store::i()->dtproxy_md5 = $md5Files;
    }

    /**
     * makes the files for php-toolbox plugin
     *
     * @param $step
     *
     * @return null|string
     */
    public function makeToolboxMeta($step)
    {
        if ($this->doProxies) {
            switch ($step) {
                default:
                    $path = \IPS\Application::getRootPath() . '/applications/toolbox/data/defaults/';
                    $jsonMeta = json_decode(file_get_contents($path . 'defaults.json'), true);
                    $jsonMeta2 = json_decode(file_get_contents($path . 'defaults2.json'), true);
                    $jsonMeta += $jsonMeta2;
                    Store::i()->dt_json = $jsonMeta;
                    Applications::i()->create();
                    $step = 'db';
                    break;
                case 'db':
                    GeneratorDb::i()->create();
                    $step = 'lang';
                    break;
                case 'lang':
                    Language::i()->create();
                    $step = 'ext';
                    break;
                case 'ext':
                    Extensions::i()->create();
                    $step = 'temp';
                    break;
                case 'temp':
                    Templates::i()->create();
                    $step = 'mod';
                    break;
                case 'mod':
                    Moderators::i()->create();
                    $step = 'url';
                    break;
                case 'url':
                    Url::i()->create();
                    $step = 'json';
                    break;
                case 'json':
                    $this->makeJsonFile();
                    $step = null;
                    break;
            }
        } else {
            $step = null;
        }

        return $step;
    }

    /**
     * creates the .ide-toolbox.metadta.json
     */
    public function makeJsonFile()
    {
        $jsonMeta = [];

        if (isset(Store::i()->dt_json)) {
            $jsonMeta = Store::i()->dt_json;
        }
        /* @var \IPS\Application $app */
        foreach (Application::appsWithExtension('toolbox', 'Providers', false) as $app) {
            /* @var Providers $extension */
            foreach ($app->extensions('toolbox', 'Providers') as $extension) {
                $extension->meta($jsonMeta);
                $extension->writeProvider(Writer::i());
            }
        }

        if (empty($jsonMeta) === false) {
            try {
                $this->_writeFile('.ide-toolbox.metadata.json', json_encode($jsonMeta, JSON_PRETTY_PRINT), $this->save);
            }catch(\OutOfRangeException $e){}
            try{
            $this->_writeFile('errocodes.json', json_encode(Store::i()->dt_error_codes, JSON_PRETTY_PRINT), $this->save);
            }catch(\OutOfRangeException $e){}
            try{
            $this->_writeFile('altcodes.json', json_encode(Store::i()->dt_error_codes2, JSON_PRETTY_PRINT), $this->save);
            }catch(\OutOfRangeException $e){}
            try{
            $this->_writeFile('bitwise.json', json_encode(Store::i()->dt_bitwise_files, JSON_PRETTY_PRINT), $this->save);
            }catch(\OutOfRangeException $e){}
            try{
            $this->_writeFile('interfaces.json',json_encode(Store::i()->dt_interfacing, JSON_PRETTY_PRINT),$this->save);
            }catch(\OutOfRangeException $e){}
            try{
            $this->_writeFile('traits.json',json_encode(Store::i()->dt_traits, JSON_PRETTY_PRINT),$this->save);
            }catch(\OutOfRangeException $e){}
            unset(
                Store::i()->dt_error_codes,
                Store::i()->dt_error_codes2,
                Store::i()->dt_json,
                Store::i()->dt_bitwise_files,
                Store::i()->dt_interfacing,
                Store::i()->dt_traits
            );
        }
    }

    public function buildCss()
    {
        $ds = DIRECTORY_SEPARATOR;
        $save = $this->save . $ds . 'css' . $ds;
            $this->emptyDirectory($save);
        if (!is_dir($save) && !mkdir($save) ) {
            chmod($save, 0777);
        }
        $finder = new Finder();

        $finder->in(\IPS\Application::getRootPath());
        foreach ($this->excludedDirCss() as $dirs) {
            $finder->exclude($dirs);
        }

        foreach ($this->excludedFilesCss() as $file) {
            $finder->notName($file);
        }
        $filter = function (SplFileInfo $file) {
            if (!in_array($file->getExtension(), ['css'])) {
                return false;
            }

            return true;
        };

        /** @var \Symfony\Component\Finder\SplFileInfo $css */
        foreach ($finder->filter($filter)->files() as $css) {
            try {
                $functionName = 'css_' . mt_rand();
                $contents = str_replace('\\', '\\\\', $css->getContents());
                /* If we have something like `{expression="\IPS\SOME_CONSTANT"}` we cannot double escape it, however we do need to escape font icons and similar. */
                $contents = preg_replace_callback("/{expression=\"(.+?)\"}/ms", function ($matches) {
                    return '{expression="' . str_replace('\\\\', '\\', $matches[1]) . '"}';
                }, $contents);
                Theme::makeProcessFunction($contents, $functionName);
                $functionName = "IPS\Theme\\{$functionName}";
                if (!is_dir($save . $css->getRelativePath() . $ds)) {
                    mkdir($save . $css->getRelativePath() . $ds, 0777, true);
                    chmod($save . $css->getRelativePath() . $ds, 0777);
                }
                //_p( $css->getRelativePath(), $css->getBasename(),$functionName());
                file_put_contents($save . $css->getRelativePath() . $ds . $css->getBasename(), $functionName());
            }
            catch(Throwable $e){
                Debug::log($e);
                Debug::log($css->getFilename());
                Debug::log($css->getRelativePath());
                continue;
            }
        }
    }

    /**
     * empties a directory, use with caution!
     *
     * @param $dir
     *
     * @throws IOException
     */
    public function emptyDirectory(?string $dir = null)
    {
        if($dir === null){
            $dir = $this->save . DIRECTORY_SEPARATOR;
        }
        try {
            $fs = new Filesystem();
            $fs->remove($dir);
        }catch(\Symfony\Component\Filesystem\Exception\IOException $e){}
    }

    public function buildMd5(){
        $store = Store::i()->dtproxy_md5??[];
        if(empty($store)) {
            $finder = new Finder();
            try {
                foreach ($this->lookIn() as $dirs) {
                    if (is_dir($dirs)) {
                        $finder->in($dirs);
                    }
                }

                foreach ($this->excludedDir() as $dirs) {
                    $finder->exclude($dirs);
                }

                foreach ($this->excludedFiles() as $file) {
                    $finder->notName($file);
                }

                $filter = function (SplFileInfo $file) {
                    if (!in_array($file->getExtension(), ['php', 'phtml'])) {
                        return false;
                    }

                    return true;
                };

                $finder->filter($filter)->files();
                foreach ($finder as $file) {
                    $path = (string)$file;
                    $store[$path] = md5_file($path);
                }
            } catch (\Throwable $e) {
            }
        }
        Store::i()->dtproxy_md5 = $store;
    }

    /**
     * this will iterator over directorys to find a list of php files to process, used in both the MR and CLI.
     *
     * @param null $dir
     * @param bool $returnIterator
     *
     * @return int|Finder
     */
    public function dirIterator(?string $dir = null, bool $returnIterator = false, bool $empty = true)
    {
        $ds = DIRECTORY_SEPARATOR;
        $save = $this->save . $ds;
        $finder = new Finder();
        try {
            if ($dir === null) {
                    if (is_dir($save) && $empty === true) {
                        //$this->emptyDirectory();
                    }

                    if(!is_dir($save) && !mkdir($save)){
                        chmod($save, 0777);
                    }

                    if (!is_dir($save . 'class' . $ds) && !mkdir($save . 'class' . $ds)) {
                        chmod($save . 'class/', 0777);
                    }

                    if (!is_dir($save . 'templates' . $ds) && !mkdir($save . 'templates' . $ds)) {
                        chmod($save . 'templates' . $ds, 0777);
                    }

                    if (!is_dir($save . 'extensions' . $ds) && !mkdir($save . 'extensions' . $ds)) {
                        chmod($save . 'extensions' . $ds, 0777);
                    }
                foreach ($this->lookIn() as $dirs) {
                    if (is_dir($dirs)) {
                        $finder->in($dirs);
                    }
                }
            } else {
                $finder->in($dir);
            }

            foreach ($this->excludedDir() as $dirs) {
                $finder->exclude($dirs);
            }

            foreach ($this->excludedFiles() as $file) {
                $finder->notName($file);
            }

            $filter = function (SplFileInfo $file) {
                if (!in_array($file->getExtension(), ['php', 'phtml'])) {
                    return false;
                }

                return true;
            };

            if (isset(Store::i()->dtproxy_proxy_files)) {
                unset(Store::i()->dtproxy_proxy_files);
            }

            $finder->filter($filter)->files();

            if ($returnIterator) {
                return $finder;
            }
            $files = array_keys(iterator_to_array($finder));
            asort($files);
            Store::i()->dtproxy_proxy_files = $files;
            return $finder->count();
        } catch (Exception $e) {
            throw $e;
            return 0;
        }
    }

    /**
     * paths to look in for php and phtml files in dirIterator
     *
     * @return array
     */
    protected function lookIn(): array
    {
        $ds = DIRECTORY_SEPARATOR;

        return [
            \IPS\Application::getRootPath() . $ds . 'applications',
            \IPS\Application::getRootPath() . $ds . 'system',
        ];
    }

    /**
     * directories to exclude when dirIterator runs
     *
     * @return array
     */
    protected function excludedDir(): array
    {
        $return = [
            'api',
            'interface',
            'data',
            'hooks',
            'setup',
            'tasks',
            'widgets',
            '3rdparty',
            '3rd_party',
            'vendor',
            'themes',
            'StormTemplates',
            'ckeditor',
            'hook_templates',
            'dtbase_templates',
            'hook_temp',
            'dtProxy',
            'plugins',
            'uploads',
            'oauth',
            'app',
            'web',
            'GraphQL',
            'AdminerDb',
        ];

        $exd = \IPS\Application::getRootPath() . '/excluded.php';
        if (file_exists($exd)) {
            require $exd;
            if (isset($excludeFolders)) {
                $return = array_merge($return, $excludeFolders);
            }
        }
        return $return;
    }

    protected function excludedDirCss(): array
    {
        $return = [
            '3rdparty',
            '3rd_party',
            'vendor',
            'dtProxy',
            'uploads',
            'AdminerDb',
        ];

        $exd = \IPS\Application::getRootPath() . '/excludedCss.php';
        if (file_exists($exd)) {
            require $exd;
            if (isset($excludeFolders)) {
                $return = array_merge($return, $excludeFolders);
            }
        }
        return $return;
    }

    protected function excludedFilesCss(): array
    {
        $return = [];

        $exf = \IPS\Application::getRootPath() . '/excludedCss.php';
        if (file_exists($exf)) {
            require $exf;
            if (isset($excludeFiles)) {
                $return = array_merge($return, $excludeFiles);
            }
        }

        return $return;
    }

    /**
     * files excluded when dirIterator runs
     *
     * @return array
     */
    protected function excludedFiles(): array
    {
        $return = [
            '.htaccess',
            'lang.php',
            'jslang.php',
            'HtmlPurifierDefinitionCache.php',
            'HtmlPurifierInternalLinkDef.php',
            'HtmlPurifierSrcsetDef.php',
            'HtmlPurifierSwitchAttrDef.php',
            'sitemap.php',
            'conf_global.php',
            'conf_global.dist.php',
            '404error.php',
            'error.php',
            'test.php',
            'HtmlPurifierHttpsImages.php',
            'system/Output/System/Output.php'
        ];

        $exf = \IPS\Application::getRootPath() . '/excluded.php';
        if (file_exists($exf)) {
            require $exf;
            if (isset($excludeFiles)) {
                $return = array_merge($return, $excludeFiles);
            }
        }

        return $return;
    }

    public function excludeClasses()
    {
        return [
            _System::class => 1
        ];
    }

    /**
     * @param      $file
     * @param bool $isTemplate
     */
    public function buildAndMake($file, $isTemplate = false)
    {
        $this->build($file);
    }

    public function buildHooks()
    {
        /** @var Application $app */
        foreach (Application::specialHooks() as $app) {
            $this->buildAppHooks($app);
        }
    }

    protected function buildAppHooks(\IPS\Application $app)
    {
        $appDir = \IPS\Application::getRootPath() . '/applications/' . $app->directory;
        $dir = $appDir . '/data/hooks.json';
        $hooks = json_decode(file_get_contents($dir), true);
        foreach ($hooks as $file => $data) {
            if (isset($data['type']) && $data['type'] === 'C') {
                $this->buildHookProxy($appDir . '/hooks/' . $file . '.php', $data['class'], $app->directory);
            }
        }
    }

    protected function buildHookProxy($hookFile, $class, $app)
    {
        $path = $this->save . '/class/hooks/';
        if (file_exists($hookFile)) {
            $add = true;
            $ns = $app . str_replace('\\', '_', $class) . '_a' . md5($path . $hookFile . time());
            $content = file_get_contents($hookFile);
            $tokenize = $this->tokenize($content);

            if (isset($tokenize['namespace']) && $tokenize['namespace'] !== null) {
                $add = false;
                $ns = $tokenize['namespace'];
            }
            if ($add === true) {
                $file = fopen($hookFile, 'rb');
                $i = 0;
                if ($file) {
                    $content = '';
                    while (($line = fgets($file)) !== false) {
                        if ($i === 0) {
                            $content .= '//<?php namespace ' . $ns . ';' . PHP_EOL;
                            $i++;
                        } else {
                            $content .= $line;
                        }
                    }
                }
                fclose($file);
            }

            file_put_contents($hookFile, $content);
            $new = new ClassGenerator();
            $new->setName('_HOOK_CLASS_');
            $new->setNamespaceName($ns);
            $new->setExtendedClass($class);
            $proxyFile = new DTFileGenerator();
            $proxyFile->isProxy = true;
            $proxyFile->setClass($new);
            $proxyFile->setFilename($path . '/' . $ns . '.php');
            $proxyFile->write();
        }
    }

    /**
     * returns the class and namespace
     *
     * @param $source
     *
     * @return array|null
     */
    public function tokenize($source)
    {
        $namespace = null;
        $tokens = token_get_all($source);
        $count = count($tokens);
        $dlm = false;
        $final = false;
        $abstract = false;

        for ($i = 2; $i < $count; $i++) {
            if ((isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] === 'phpnamespace' || $tokens[$i - 2][1] === 'namespace')) || ($dlm && $tokens[$i - 1][0] === T_NS_SEPARATOR && $tokens[$i][0] === T_STRING)) {
                if (!$dlm) {
                    $namespace = 0;
                }
                if (isset($tokens[$i][1])) {
                    $namespace = $namespace ? $namespace . "\\" . $tokens[$i][1] : $tokens[$i][1];
                    $dlm = true;
                }
            } else {
                if ($dlm && ($tokens[$i][0] !== T_NS_SEPARATOR) && ($tokens[$i][0] !== T_STRING)) {
                    $dlm = false;
                }
            }

            if ($tokens[$i][0] === T_FINAL) {
                $final = true;
            }

            if ($tokens[$i][0] === T_ABSTRACT) {
                $abstract = true;
            }

            if (
                (
                    $tokens[$i - 2][0] === T_INTERFACE || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'interface') ||
                    $tokens[$i - 2][0] === T_INTERFACE || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'trait') ||
                    $tokens[$i - 2][0] === T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'class')
                ) &&
                $tokens[$i - 1][0] === T_WHITESPACE &&
                $tokens[$i][0] === T_STRING
            ) {
                $class = $tokens[$i][1];
                return [
                    'type' => $tokens[$i - 2][0],
                    'namespace' => $namespace,
                    'class'     => $class,
                    'abstract'  => $abstract,
                    'final'     => $final,
                ];
            }
        }

        return null;
    }
}
