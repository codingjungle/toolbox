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
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Output;
use IPS\Request;
use IPS\Theme;

use function array_merge;
use function count;
use function defined;
use function file_get_contents;
use function is_array;
use function json_decode;
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

    protected static $loaded = false;

    public static function loadAutoLoader(): void
    {
        if (static::$loaded === false) {
            static::$loaded = true;
            require \IPS\Application::getRootPath('toolbox') . '/applications/toolbox/sources/vendor/autoload.php';
            IPS::$PSR0Namespaces['Generator'] = \IPS\Application::getRootPath(
                ) . '/applications/toolbox/sources/Generator/';
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
        include(\IPS\Application::getRootPath() . '/applications/toolbox/sources/Profiler/Adminer.php');
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
            if (count($application->extensions('toolbox', 'SpecialHooks'))) {
                $apps[$application->directory] = $application;
            }
        }
        return $apps;
    }

    public static function getThemeId()
    {
        $location = Dispatcher::hasInstance() ? Dispatcher::i()->controllerLocation : null;
        if (isset(Request::i()->admin) && Request::i()->admin === 1) {
            $location = 'admin';
        }
        if ($location === 'admin' && defined('DT_THEME_ID_ADMIN') && DT_THEME_ID_ADMIN !== 0) {
            return DT_THEME_ID_ADMIN;
        }

        return DT_THEME_ID;
    }

    public static function getSidebar()
    {
        $app = Application::load(Request::i()->appKey);
        $createTable = Url::internal('app=core&module=applications&controller=developer')->setQueryString([
            'appKey' => $app->directory,
            'do'     => 'addTable'
        ]);
        $schema = json_decode(file_get_contents($app->getApplicationPath() . "/data/schema.json"), true);
        $html = '<div class="ipsBox ipsPadding:half">';
        $html .= '<h4>Tables<a href="' . $createTable . '" class="ipsButton ipsButton_primary ipsButton_veryVerySmall ipsPos_right" title="Add Table" data-ipsDialog><i class="fa fa-plus"></i></a></h4>';
        $html .= '<ul class="ipsList_reset">';
        foreach ($schema as $key => $val) {
            $active = ' style="width:150px;display:block;" ';
            if (Request::i()->_name === $key) {
                $active = ' style="width:150px;display:block;text-decoration:underline;font-weight:bolder;"';
            }
            $url = Url::internal('app=core&module=applications&controller=developer')
                      ->setQueryString([
                          'appKey'   => $app->directory,
                          'do'       => 'editSchema',
                          '_name'    => $key,
                          'existing' => 1,
                          'tab'      => 'columns'
                      ]);
            $html2 = '<ul class="ipsMenu ipsHide" id="toolbox_schema_fields_' . $key . '_menu">';
            foreach ($val['columns'] as $kk => $vv) {
                $url2 = Url::internal('app=core&module=applications&controller=developer&appKey=chrono&do=editSchemaColumn&_name=chrono_timecards&column=timecard_id')
                           ->setQueryString([
                               'appKey' => $app->directory,
                               'do'     => 'editSchemaColumn',
                               '_name'  => $key,
                               'column' => $kk
                           ]);
                $html2 .= <<<EOF
<li class="ipsMenu_item">
<a href="{$url2}" data-ipsDialog data-ipsDialog-desctructOnClose="true" data-ipsDialog-title="{$kk}">{$kk}({$vv['type']})</a>
</li>
EOF;
            }
            $html2 .= '</ul>';
            $addColumn = Url::internal('app=core&module=applications&controller=developer&appKey=chrono&do=editSchemaColumn&_name=chrono_timesheets')
                            ->setQueryString([
                                'appKey' => $app->directory,
                                'do'     => 'editSchemaColumn',
                                '_name'  => $key
                            ]);
            $html .= <<<EOF
<li class="ipsClearfix ipsMargin_bottom:half"> 
<a href="{$url}"{$active} class="ipsPos_left ipsType_break">
{$key}
</a> 
<a href="{$addColumn}" class="ipsButton ipsButton_alternate ipsButton_veryVerySmall ipsPos_right" data-ipsdialog data-ipsDialog-desctructOnClose="true" data-ipsDialog-title="Add Column"><i class="fa fa-plus"></i></a>
<a href="#toolbox_schema_fields_{$key}_menu" id="toolbox_schema_fields_{$key}" class="ipsButton ipsButton_positive ipsButton_veryVerySmall ipsPos_right" data-ipsMenu><i class="fa fa-chevron-circle-down"></i></a>
{$html2} 
</li>
EOF;
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function get__icon()
    {
        return 'wrench';
    }
}
