<?php

/**
 * @brief       ParserAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Code Analyzer
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Code\Abstracts;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Output;
use IPS\Request;
use IPS\toolbox\Editor;
use IPS\toolbox\extensions\toolbox\codeAnalyzer\Core;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

abstract class _ParserAbstract
{
    /**
     * @var Finder
     */
    protected $files = [];

    /**
     * @var null|string
     */
    protected $appPath;

    /**
     * @var Application
     */
    protected $app;

    /**
     * _ParserAbstract constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        try {
            \IPS\toolbox\Application::loadAutoLoader();
            if (!($app instanceof Application)) {
                $app = Application::load($app);
            }
            $this->app = $app;
            $this->getFiles();
        } catch (Exception $e) {
        }

        //we do this so we can capture the fatal and redirect if need be
        ob_start();

        register_shutdown_function(function () {
            $error = error_get_last();
            $url = \IPS\Request::i()->url();
            if ($error['type'] === E_COMPILE_ERROR) {
                $url = $url->setQueryString(['do' => 'glitch'])->stripQueryString(['csrfKey', 'mr', 'download']);
                $url = (string)$url;
                Store::i()->toolbox_code_analyzer_interrupted = $error;
                if (Request::i()->isAjax()) {
                    Output::i()->json(array(
                            'redirect' => (string)$url,
                            'message' => ''
                        )
                    );
                } else {
                    header("Location: {$url}");
                }
            }
        });
    }

    protected function getFiles()
    {
        $this->files = $this->getLocalFiles();
    }

    protected function getAppPaths()
    {
        $paths[] = (new \IPS\toolbox\extensions\toolbox\codeAnalyzer\Core())->getPaths(get_called_class(), $this->app);
        $extension = $this->app->extensions('toolbox', 'codeAnalyzer');
        if(empty($extension) === false){

            foreach($extension as $ext){
                $path = $ext->getPaths(get_called_class(), $this->app);
                if($path !== null){
                    $paths[] = $path;
                }
            }
        }

        if(empty($paths) === false){
            return array_merge(...$paths);
        }
        return $this->app->getApplicationPath() . '/';
    }

    /**
     * gathers all the files in an app directory except the lang.php, jslang.php and lang.xml
     *
     * @throws InvalidArgumentException
     */
    final protected function getLocalFiles()
    {
        $files = (new Finder());
        $paths = $this->getAppPaths();
        if(empty($paths) === false && \is_array($paths)){
            foreach($paths as $path){
                $files->in($path);
            }
        }
        else{
            $files->in($paths);
        }

        if(empty($this->getNames()) === false){
            foreach($this->getNames() as $names){
                $files->name($names);
            }
        }

        $filter = function (SplFileInfo $file) {
            if (!in_array($file->getExtension(), $this->getExtensions())) {
                return false;
            }

            return true;
        };
        $files->filter($filter);

        if(empty($this->excludedFolders()) === false){
            foreach ($this->excludedFolders() as $dirs) {
                $files->exclude($dirs);
            }
        }

        if (empty($this->excludedFiles()) === false) {
            foreach ($this->excludedFiles() as $name) {
                $files->notName($name);
            }
        }

        return $files->files();
    }
    public function getNames(){
        $extension = $this->app->extensions('toolbox', 'codeAnalyzer');
        if(empty($extension) === false){
            $merged = [];
            foreach($extension as $ext){
                $files = $ext->getNames(get_called_class());
                if($files !== null){
                    $merged = array_merge($merged, $files);
                }
            }
            if(empty($merged) === false){
                return $merged;
            }
        }
        return null;
    }
    public function getExtensions(){
        $extension = $this->app->extensions('toolbox', 'codeAnalyzer');
        if(empty($extension) === false){
            $merged = [];
            foreach($extension as $ext){
                $files = $ext->getExtensions(get_called_class());
                if($files !== null){
                    $merged = array_merge($merged, $files);
                }
            }
            if(empty($merged) === false){
                return $merged;
            }
        }
        return (new Core())->getExtensions(get_called_class());
    }
    public function excludedFiles(){
        $extension = $this->app->extensions('toolbox', 'codeAnalyzer');
        if(empty($extension) === false){
            $merged = [];
            foreach($extension as $ext){
                $files = $ext->excludedFiles(get_called_class());
                if($files !== null){
                    $merged = array_merge($merged, $files);
                }
            }
            if(empty($merged) === false){
                return $merged;
            }
        }
        return (new Core())->excludedFiles(get_called_class());
    }

    public function excludedFolders(){
        $extension = $this->app->extensions('toolbox', 'codeAnalyzer');
        if(empty($extension) === false){
            $merged = [];
            foreach($extension as $ext){
                $folders = $ext->excludedFolders(get_called_class());
                if($folders !== null){
                    $merged = array_merge($merged, $folders);
                }
            }
            if(empty($merged) === false){
                return $merged;
            }
        }
        return (new Core())->excludedFolders(get_called_class());
    }

    /**
     * looks for usage in app files
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function check(): array
    {
        return [];
    }

    /**
     * looks for used to see if they are defined
     *
     * @return array
     */
    public function verify(): array
    {
        return [];
    }

    /**
     * stores all the contents from the files for searching thru.
     *
     * @return string
     * @throws RuntimeException
     */
    protected function getContent(): string
    {
        $files = $this->getLocalFiles();
        $content = '';
        /**
         * @var SplFileInfo $file
         */
        foreach ($files as $file) {
            $content .= $file->getContents();
        }
        return $content;
    }

    /**
     * builds a url for the file to open it up in an editor
     *
     * @param $path
     * @param $line
     *
     * @return mixed|null
     */
    protected function buildPath($path, $line)
    {
        return (new Editor())->replace($path, $line);
    }
}