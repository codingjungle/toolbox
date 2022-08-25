<?php

/**
 * @brief       Application Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\DevCenter\Sources\Generator;

use IPS\Output;
use IPS\Theme;
use Throwable;
use UnderflowException;

use IPS\toolbox\Proxy\Proxyclass;

use function class_exists;
use function explode;
use function var_export;

use const STR_PAD_LEFT;

class _Application extends GeneratorAbstract
{
    protected $overrideDir = true;
    protected $includeConstructor = false;
    /**
     * @inheritdoc
     */
    protected function bodyGenerator()
    {
        $this->dir = $this->application->getApplicationPath().'/';
        $og = '\\IPS\\' . $this->application->directory . '\\ApplicationOG';
        if (!class_exists($og)) {
            $path = $this->application->getApplicationPath();
            $file = $path . '/Application.php';
            $content = \file_get_contents($file);
            $content = str_replace('_Application', '_ApplicationOG', $content);
            $newPath = $path . '/sources/ApplicationOG/';
            if (!\is_dir($newPath)) {
                \mkdir($newPath, 0777, true);
            }
            \file_put_contents($newPath . '/ApplicationOG.php', $content);
            Proxyclass::i()->build($newPath . '/ApplicationOG.php');

        }
        $this->brief = 'Application Class';
        $this->extends = $og;
        $addTo = $this->addToApplications;
        $body = '';
        $this->generator->addImport(Output::class);
        $this->generator->addImport(Theme::class);
        $this->generator->addImportFunction('array_merge');
        $this->generator->addImportFunction('array_combine');
        $this->generator->addImportFunction('strrev');
        $this->generator->addImportFunction('dechex');
        $this->generator->addImportFunction('crc32');
        $this->generator->addImportFunction('mb_substr');
        $this->generator->addImportFunction('mb_strlen');
        $this->generator->addImportFunction('str_pad');
        $this->generator->addImportFunction('random_int');
        $this->generator->addImportFunction('floor');
        $this->generator->addImportConstant('STR_PAD_LEFT');

        foreach ($addTo as $add) {
            switch ($add) {
                case 'js':
                    $body .= <<<eof


    /**
     * @param array \$files an array of js files to load, without .js, eg ['front_myjs','front_myjs2'],
     * will use the app it is called from, but you can load other apps js if need be by adding the app
     * to the value in the array, eg ['core_front_somejs','front_myjs','front_myjs2'], the first
     * value will load from core, the next 2 will load from your app.
     * @return void
     */
    public static function addJs(array \$files): void
    {
        \$app = '{$this->application->directory}';
        \$jsFiles[] = Output::i()->jsFiles;
        foreach (\$files as \$f) {
            \$v = explode('_', \$f);
            //determine if we need to change the \$app
            if(\count(\$v) === 2){
                [\$loc, \$file] = explode('_',\$f);
            }
            else {
                [\$app, \$loc, \$file] =  explode('_',\$f);
            }
            \$file = \$loc . '_' . \$file . '.js';
            //add to local variable for merging
            \$jsFiles[] = Output::i()->js(\$file, \$app, \$loc);
        }
        //merges \$jsFiles into Output::i()->jsFiles
        Output::i()->jsFiles = array_merge(...\$jsFiles);
    } 
eof;

                    break;
                case 'css':
                    $body .= <<<eof


    /**
     * @param array \$files an array of css files to load, without .css, eg ['front_mycss','front_mycss2'],
     * will use the app it is called from, but you can load other apps css if need be by adding the app
     * to the value in the array, eg ['core_front_somecss','front_mycss','front_mycss2'], the first
     * value will load from core, the next 2 will load from your app.
     * @return void
     */
    public static function addCss(array \$files): void
    { 
        \$app = '{$this->application->directory}';
        \$cssFiles[] = Output::i()->cssFiles;
        foreach (\$files as \$f) {
            \$v = explode('_', \$f);
            //determine if we need to change the \$app
            if(\count(\$v) === 2){
                [\$loc, \$file] = explode('_',\$f);
            }
            else {
                [\$app, \$loc, \$file] =  explode('_',\$f);
            }
            \$file = \$loc . '_' . \$file . '.css';
            \$cssFiles[] = Theme::i()->css(\$file, \$app, \$loc);
        }
        //merges \$cssFiles into Output::i()->cssFiles
        Output::i()->cssFiles = array_merge(...\$cssFiles);
    }
eof;
                    break;
                case 'jsVar':
                    $body .= <<<eof


    /**
     * @param array \$jsVars a key/value array of jsVars to add, ['mykey' => 'value']
     * @return void
     */
    public static function addJsVar(array \$jsVars): void
    {
        foreach (\$jsVars as \$key => \$jsVar) {
            Output::i()->jsVars[\$key] = \$jsVar;
        }
    }
eof;
                    break;
                case 'color':
                    $body .= <<<eof


    /**
     * @param string \$name some name to convert to a color
     * @return string
     */
    public static function color(string \$name): string
    {
        \$str = strrev(\$name);
        \$code = dechex(crc32(\$str));
        \$code = mb_substr(\$code, 0, 6);
        if (mb_strlen(\$code) !== 6) {
            return '#e5af25';
        }

        return '#' . \$code;
    }
eof;
                    break;
                case 'quickColor':
                    $body .= <<<eof


    /**
     * quick color, call for a random color
     * @return string
     * @throws Exception
     */
    public static function quickColor(): string
    {
        return '#' . str_pad(dechex(random_int(0, 0xFFFFFF)), 6, 0, STR_PAD_LEFT);
    }
eof;
                    break;
                case 'convertTime':
                    $body .= <<<eof


    /**
     * @param int \$seconds the number of seconds to convert to hours/minutes/seconds
     * @return array
     */
    public static function convertTime(\$seconds){
        \$hours = floor(\$seconds / 3600);
        \$minutes = floor((\$seconds / 60) % 60);
        \$seconds = \$seconds % 60;

        return [
            'hours' => \$hours,
            'minutes' => \$minutes,
            'seconds' => \$seconds
        ];
    }
eof;
                    break;
                case 'frontNavigation':
                    $rootTabs = '[]';
                    $browseTabs = '[]';
                    $browseTabsEnd = '[]';
                    $activityTabs = '[]';

                    if (empty($this->rootTabs) === false) {
                        $rootTabs = "[\n";
                        foreach ($this->rootTabs as $tab) {
                            $rootTabs .= "                ['key' => '{$tab}'],\n";
                        }
                        $rootTabs .= "            ]";
                    }

                    if (empty($this->browseTabs) === false) {
                        $browseTabs = "[\n";
                        foreach ($this->browseTabs as $tab) {
                            $browseTabs .= "                ['key' => '{$tab}'],\n";
                        }
                        $browseTabs .= "            ]";
                    }

                    if (empty($this->browseTabsEnd) === false) {
                        $browseTabsEnd = "[\n";
                        foreach ($this->browseTabsEnd as $tab) {
                            $browseTabsEnd .= "                ['key' => '{$tab}'],\n";
                        }
                        $browseTabsEnd .= "            ]";
                    }

                    if (empty($this->activityTabs) === false) {
                        $activityTabs = "[\n";
                        foreach ($this->activityTabs as $tab) {
                            $activityTabs .= "                ['key' => '{$tab}'],\n";
                        }
                        $activityTabs .= "            ]";
                    }


                    $body .= <<<eof


    public function defaultFrontNavigation(): array
    {
        return [
            'rootTabs'      => {$rootTabs},
            'browseTabs'    => {$browseTabs},
            'browseTabsEnd' => {$browseTabsEnd},
            'activityTabs'  => {$activityTabs},
        ];
    }
eof;

                    break;
            }
        }
        $this->generator->addClassBody($body);
    }
}
