//<?php namespace toolbox_IPS_Theme_a76f8f4fb56ebd68f14c8a15e8155b55b;

use Exception;
use IPS\Settings;
use IPS\Theme\Advanced\Theme;
use IPS\toolbox\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function defined;
use function file_put_contents;

use function function_exists;

use const E_ALL;
use const DT_THEME;
use const IPS\IN_DEV;
use const DT_THEME_ID;
use const IPS\NO_WRITES;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class toolbox_hook_Theme extends _HOOK_CLASS_
{
    public static function i()
    {
        $return = parent::i();
        if (
                IN_DEV === true &&
                defined('DT_THEME') &&
                defined('DT_THEME_ID') &&
                DT_THEME === true &&
                DT_THEME_ID !== 0
        ) {
            static::themes();

            static::$memberTheme = new Theme();

            /* Add in the default theme properties (_data array, etc) */
            foreach (static::$multitons[Application::getThemeId()] as $k => $v) {
                static::$memberTheme->$k = $v;
                $return->{$k} = $v;
            }
        }

        return $return;
    }

    public static function runProcessFunction($content, $functionName)
    {
        $path = \IPS\Application::getRootPath() . '/toolbox_templates/';

        $filename = $path . $functionName . md5($content) . '.php';
        /* If it's already been built, we don't need to do it again */
        if (function_exists('IPS\Theme\\' . $functionName)) {
            return;
        }

        if (
                IN_DEV === true &&
                NO_WRITES === false &&
                mb_strpos($functionName, 'css_') === false &&
                Settings::i()->toolbox_debug_templates
        ) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            if (!file_exists($filename)) {
                try {
                    Application::loadAutoLoader();
                    $finder = new Finder();
                    $finder->in($path)->files()->name($functionName . '*.php');
                    $fs = new Filesystem();
                    foreach ($finder as $f) {
                        $fs->remove($f->getRealPath());
                    }
                } catch (Exception $e) {
                }
                if (IN_DEV === true && defined('DT_THEME') && defined('DT_THEME_ID') && DT_THEME === true && DT_THEME_ID !== 0) {
                    $content = static::buildContent($content);
                }
                $content = <<<EOF
<?php
namespace IPS\Theme;

{$content}
EOF;

                try {
                    file_put_contents($filename, $content);
                } catch (Exception $e) {
                }
            }

            include_once($filename);
        } else {
            if (IN_DEV === true && defined('DT_THEME') && defined('DT_THEME_ID') && DT_THEME === true && DT_THEME_ID !== 0) {
                $content = static::buildContent($content);
            }
            parent::runProcessFunction($content, $functionName);
        }
    }


    protected static function buildContent($content)
    {
        return <<<EOF

use function count;
use function in_array;
use function is_array;
use function is_object;
use function array_slice;
use function boolval;
use function chr;
use function doubleval;
use function floatval;
use function func_get_args;
use function func_num_args;
use function get_called_class;
use function get_class;
use function gettype;
use function intval;
use function is_double;
use function is_int;
use function is_integer;
use function is_long;
use function is_null;
use function is_numeric;
use function is_real;
use function is_resource;
use function is_string;
use function ord;
use function strval;
use function function_exists;
use function is_callable;
use function extension_loaded;
use function dirname;
use function constant;
use function define;
use function call_user_func;
use function call_user_func_array;  
{$content}
EOF;
    }
}
