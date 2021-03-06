<?php
/**
 * @brief            Dev Toolbox: Base Application Class
 * @author           -storm_author-
 * @copyright        -storm_copyright-
 * @package          Invision Community
 * @subpackage       Dev Toolbox: Base
 * @since            02 Apr 2018
 * @version          -storm_version-
 */

namespace IPS\toolbox;

use IPS\Application;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Theme;

use const IPS\ROOT_PATH;

use function array_merge;
use function is_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function preg_replace_callback;
use function str_replace;


/**
 * Dev Toolbox: Base Application Class
 */
class _Application extends Application
{

    public static $toolBoxApps = [
        'toolbox',
        'toolbox',
        'dtproxy',
        'dtprofiler',
    ];

    /**
     * @var string
     */
    protected static $baseDir = \IPS\ROOT_PATH . '/applications/toolbox/sources/vendor/';

    protected static $loaded = \false;

    public static function loadAutoLoader(): void
    {
        if (static::$loaded === \false) {
            static::$loaded = \true;
            require static::$baseDir . '/autoload.php';
            \IPS\IPS::$PSR0Namespaces['Generator'] = ROOT_PATH . '/applications/toolbox/sources/Generator/';
        }
    }

    public static function templateSlasher($source)
    {
        $replace = [
            'array_slice',
            'boolval',
            'chr',
            'count',
            'doubleval',
            'floatval',
            'func_get_args',
            'func_get_args',
            'func_num_args',
            'get_called_class',
            'get_class',
            'gettype',
            'in_array',
            'intval',
            'is_array',
            'is_bool',
            'is_double',
            'is_float',
            'is_int',
            'is_integer',
            'is_long',
            'is_null',
            'is_numeric',
            'is_object',
            'is_real',
            'is_resource',
            'is_string',
            'ord',
            'strval',
            'function_exists',
            'is_callable',
            'extension_loaded',
            'dirname',
            'constant',
            'define',
            'call_user_func',
            'call_user_func_array',
        ];

        foreach ($replace as $value) {
            $rep = '\\' . $value;
            $callback = function ($m) use ($rep) {
                return $rep;
            };
            $source = preg_replace_callback("#(?<!\\\\)\b" . $value . '\b#u', $callback, $source);
            $source = str_replace(
                ['function \\', 'const \\', "::\\", "$\\", "->\\"],
                [
                    'function ',
                    'const ',
                    '::',
                    '$',
                    '->',
                ],
                $source
            );
        }

        return $source;
    }

    public static function addJsVar(array $jsVars): void
    {
        foreach ($jsVars as $key => $jsVar) {
            Output::i()->jsVars[$key] = $jsVar;
        }
    }

    public static function addJs($js, $location = 'front', $app = 'toolbox'): void
    {
        if (!is_array($js)) {
            $js = [$js];
        }
        $jsFiles[] = Output::i()->jsFiles;
        foreach ($js as $file) {
            $file .= '.js';
            $jsFiles[] = Output::i()->js($file, $app, $location);
        }
        Output::i()->jsFiles = array_merge(...$jsFiles);
    }

    public static function getAdminer()
    {
        \IPS\toolbox\Application::addCss(['adminer']);
        $_GET["username"] = "michael";

        $content = '<div id="toolboxAdminer">';
        ob_start();
        include(\IPS\ROOT_PATH . '/applications/toolbox/sources/Profiler/Adminer.php');
        $content .= ob_get_clean();
        ob_end_clean();
        $content .= "</div>";
        return $content;
    }

    public static function addCss($css, $location = 'front', $app = 'toolbox'): void
    {
        if (!is_array($css)) {
            $css = [$css];
        }

        $cssFiles[] = Output::i()->cssFiles;
        foreach ($css as $file) {
            $file .= '.css';
            $cssFiles[] = Theme::i()->css($file, $app, $location);
        }
        Output::i()->cssFiles = array_merge(...$cssFiles);
    }

    public static function specialHooks()
    {
        $apps = array();
        foreach (static::applications() as $application) {
            if (\count($application->extensions('toolbox', 'SpecialHooks'))) {
                $apps[$application->directory] = $application;
            }
        }
        return $apps;
    }

    public static function getThemeId()
    {
        $location = \IPS\Dispatcher::hasInstance() ? \IPS\Dispatcher::i()->controllerLocation : null;
        if (isset(\IPS\Request::i()->admin) && \IPS\Request::i()->admin === 1) {
            $location = 'admin';
        }
        if ($location === 'admin' && \defined('DT_THEME_ID_ADMIN') && DT_THEME_ID_ADMIN !== 0) {
            return DT_THEME_ID_ADMIN;
        }

        return DT_THEME_ID;
    }

    /**
     * @inheritdoc
     */
    protected function get__icon()
    {
        return 'wrench';
    }

    public static function getSidebar(){
        $app = Application::load( Request::i()->appKey );
        $schema	= json_decode( file_get_contents( $app->getApplicationPath() . "/data/schema.json" ), TRUE );
        $html = '<div class="ipsBox ipsPadding:half">';
        $html .= '<h4>Tables</h4>';
        $html .= '<ul class="ipsList_reset">';
        foreach($schema as $key => $val){
            $active = '';
            if( \IPS\Request::i()->_name === $key){
                $active = ' style="text-decoration:underline;font-weight:bolder;"';
            }
            $url = Url::internal('app=core&module=applications&controller=developer')
                ->setQueryString([
                                     'appKey' => $app->directory,
                                     'do' => 'editSchema',
                                     '_name' => $key,
                                     'existing' => 1,
                                     'tab' => 'columns'
                                 ]);
            $html .=<<<EOF
<li>
<a href="{$url}"{$active}>
{$key}
</a>
</li>
EOF;

        }

        $html .= '</ul></div>';

        return $html;
    }
}
