<?php

/**
 * @brief       Settings Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.1.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\modules\admin\settings;

use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\IPS;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\toolbox\Form;
use IPS\toolbox\GitHooks;
use RuntimeException;

use function _p;
use function filemtime;
use function array_merge;
use function defined;
use function explode;
use function file_exists;
use function preg_replace;
use function trigger_error;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function header;
use function implode;
use function is_file;
use function preg_replace_callback;
use function property_exists;
use function random_int;
use function sha1;
use function str_replace;

use function time;

use const E_USER_ERROR;
use const DIRECTORY_SEPARATOR;
use const IPS\NO_WRITES;
use const IPS\SITE_FILES_PATH;


\IPS\toolbox\Application::loadAutoLoader();

/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * settings
 */
class _settings extends Controller
{
    /**
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;

    /**
     * Execute
     *
     * @return    void
     * @throws RuntimeException
     */
    public function execute()
    {
        \IPS\toolbox\Application::loadAutoLoader();

        Dispatcher\Admin::i()->checkAcpPermission('settings_manage');
        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        if (NO_WRITES === false) {
            if (!property_exists(IPS::class, 'beenPatched')) {
                Output::i()->sidebar['actions']['init'] = [
                    'icon'  => 'plus',
                    'title' => 'Patch init.php',
                    'link'  => Request::i()->url()->setQueryString(['do' => 'patchInit']),

                ];
            }

//            if (property_exists(IPS::class, 'beenPatched') && IPS::$beenPatched === true) {
//                Output::i()->sidebar[ 'actions' ][ 'writeSpecialHooks' ] = [
//                    'icon'  => '',
//                    'title' => 'Add Special Hooks',
//                    'link'  => Request::i()->url()->setQueryString(['do' => 'writeSpecialHooks']),
//
//                ];
//
//                Output::i()->sidebar[ 'actions' ][ 'removeSpecialHooks' ] = [
//                    'icon'  => '',
//                    'title' => 'Remove Special Hooks',
//                    'link'  => Request::i()->url()->setQueryString(['do' => 'removeSpecialHooks']),
//
//                ];
//            }

//            if (!function_exists('_p')) {
//                Output::i()->sidebar['actions']['helpers'] = [
//                    'icon'  => 'plus',
//                    'title' => 'Patch Helpers',
//                    'link'  => Request::i()->url()->setQueryString(['do' => 'patchHelpers']),
//
//                ];
//            }
        }

        $form = Form::create()->setObject(Settings::i());
        $form->addTab('toolbox');
        $form->addElement('toolbox_debug_templates', 'yn');
        $form->addElement('toolbox_use_tabs_applications','yn');
        /* @var \IPS\toolbox\extensions\toolbox\Settings\settings $extension */
        foreach (Application::allExtensions('toolbox', 'settings') as $extension) {
            $extension->elements($form);
        }

        /**
         * @var Form $form
         */
        if ($values = $form->values()) {
            /** @var Application $app */
            foreach (Application::appsWithExtension('toolbox', 'settings') as $app) {
                $extensions = $app->extensions('toolbox', 'settings', true);
                /* @var \IPS\toolbox\extensions\toolbox\Settings\_settings $extension */
                foreach ($extensions as $extension) {
                    $extension->formatValues($values);
                }
            }
            $form->saveAsSettings($values);
            Output::i()->redirect($this->url, 'Settings Saved');
        }
        Output::i()->title = 'Settings';
        Output::i()->output = $form;
    }

//    protected function writeSpecialHooks()
//    {
//        $apps = Application::appsWithExtension('toolbox', 'SpecialHooks');
//
//        (new GitHooks($apps))->writeSpecialHooks();
//
//        Output::i()->redirect($this->url->setQueryString(['tab' => ''])->csrf(), 'SpecialHooks Created');
//    }

//    protected function removeSpecialHooks()
//    {
//        $apps = Application::appsWithExtension('toolbox', 'SpecialHooks');
//
//        (new GitHooks($apps))->removeSpecialHooks();
//
//        Output::i()->redirect($this->url->setQueryString(['tab' => ''])->csrf(), 'SpecialHooks Removed');
//    }

    protected function patchHelpers()
    {
        if (NO_WRITES === false && !function_exists('_p')) {
            $path = \IPS\Application::getRootPath() . DIRECTORY_SEPARATOR;
            $init = $path . 'init.php';
            $content = file_get_contents($init);

            if (!is_file(\IPS\Application::getRootPath() . DIRECTORY_SEPARATOR . 'init.bu.php')) {
                file_put_contents(\IPS\Application::getRootPath() . DIRECTORY_SEPARATOR . 'init.bu.php', $content);
            }
            $r = <<<EOF
require __DIR__ . '/applications/toolbox/sources/Debug/Helpers.php';
class IPS
EOF;
            $content = str_replace('class IPS', $r, $content);
            file_put_contents($init, $content);
        }

        Output::i()->redirect($this->url, 'init.php patched with Debug Helpers');
    }

    protected function patchInit()
    {
        if (NO_WRITES === false && !property_exists(IPS::class, 'beenPatched')) {
            $path = \IPS\Application::getRootPath() . DIRECTORY_SEPARATOR;
            $init = $path . 'init.php';
            $content = file_get_contents($init);
            if (!is_file(\IPS\Application::getRootPath() . DIRECTORY_SEPARATOR . 'init.bu.php')) {

                $preg = "#class IPS$#msu";
                $content = preg_replace_callback($preg, static function ($e) {
                    return 'class IPSOG';
                }, $content);
                file_put_contents(\IPS\Application::getRootPath() . DIRECTORY_SEPARATOR . 'init.original.php', $content);
                $preg = "#class IPSOG$#msu";
                $content = preg_replace_callback($preg, static function ($e) {
                    return 'class IPSBU';
                }, $content);
                $content = str_replace('\\IPS\\IPS','static',$content);
                $content = str_replace('IPS::init();','//IPS::init();', $content);
                $content = str_replace('self::', 'static::', $content);

                file_put_contents(\IPS\Application::getRootPath() . DIRECTORY_SEPARATOR . 'init.bu.php', $content);
            }
//            $content = str_replace('self::monkeyPatch', 'static::monkeyPatch', $content);
//            $preg = "#class IPS$#msu";
//            $content = preg_replace_callback($preg, static function ($e) {
//                return 'class IPSBU';
//            }, $content);
//            $preg = "#^IPS::init\(\);#msu";
//            $content = preg_replace_callback($preg, static function ($e) {
//
//            }, $content);
//            $preg = "#public static function monkeyPatch\((.*?)public#msu";
//            $before = <<<'eof'
//
//eof;
//            $content = preg_replace_callback(
//                $preg,
//                function ($e) use ($before) {
//                    return $before . "\n\n  public";
//                },
//                $content
//            );

            $content = <<<'eof'
<?php
namespace IPS;
require 'init.bu.php';
class IPS extends \IPS\IPSBU {
    public static $beenPatched = true;
    public static function exceptionHandler( $exception )
	{
	    if(\IPS\IN_DEV === true){
	        throw $exception;
	    }
	    else{
	        parent::exceptionHandler($exception);
	    }
	}

    public static function monkeyPatch($namespace, $finalClass, $extraCode = '')
    {
        $realClass = "_{$finalClass}";
        if (isset(self::$hooks[ "\\{$namespace}\\{$finalClass}" ]) AND \IPS\RECOVERY_MODE === false) {
            $path = ROOT_PATH . '/hookTemp/';
            if (!\is_dir($path)) {
                \mkdir($path, 0777, true);
            }

            $vendor = ROOT_PATH.'/applications/toolbox/sources/vendor/autoload.php';
            require $vendor;

            foreach (self::$hooks[ "\\{$namespace}\\{$finalClass}" ] as $id => $data) {
                $rpath = ROOT_PATH;
                if ( \IPS\CIC2 AND static::isThirdParty( $data['file'] ) )
                {
                    $rpath = SITE_FILES_PATH;
                }
                $mtime = filemtime( $rpath . '/' . $data[ 'file' ] );
                $name = \str_replace(["\\", '/'], '_', $namespace . $realClass . $finalClass . $data[ 'file' ]);
                $filename = $name.'_' . $mtime . '.php';

                if (!file_exists( $path.$filename) && \file_exists($rpath . '/' . $data[ 'file' ]))
                {

                    $fs = new \Symfony\Component\Filesystem\Filesystem();
                    $finder = new \Symfony\Component\Finder\Finder();
                    $finder->in( $path )->files()->name($name.'*.php');

                    foreach( $finder as $f ){
                        $fs->remove($f->getRealPath());
                    }

                    $content = file_get_contents($rpath . '/' . $data[ 'file' ]);
                    $content = preg_replace('#\b(?<![\'|"])_HOOK_CLASS_\b#', $realClass, $content);
                    $content = preg_replace( '#\b(?<![\'|"])_HOOK_CLASS_'.$data['class'].'\b#', $realClass, $content);
                    $contents = "namespace {$namespace}; " . $content;
                    if (!\file_exists($path . $filename)) {
                        \file_put_contents($path . $filename, "<?php\n\n" . $contents);
                    }
                }
                if( static::isThirdParty( $data['file'] ) )
                {
                    static::$loadedHooks[] = $data['file'];
                }
                require_once $path . $filename;
                $realClass = $data[ 'class' ];
            }
        }

        $reflection = new \ReflectionClass("{$namespace}\\_{$finalClass}");
        if (eval("namespace {$namespace}; " . $extraCode . ($reflection->isAbstract() ? 'abstract' : '') . " class {$finalClass} extends {$realClass} {}") === false)       {
            trigger_error("There was an error initiating the class {$namespace}\\{$finalClass}.", E_USER_ERROR);
        }
    }
}
IPS::init();
eof;
            file_put_contents($init, $content);
        }

        Output::i()->redirect($this->url, 'init.php patched');
    }

    public function package()
    {
        $package = <<<EOF
{
  "name": "toolbox.js",
  "version": "#version#",
  "description": "the nodejs modules for toolbox",
  "main": "toolbox.js",
  "author": "codingjungle.com",
  "dependencies": {
    "express": "^4.14.0",
    "socket.io": "^4.5.1"
  }
}
EOF;
        $find = ['#version#'];
        $replace = [$this->buildVersion(Application::load('toolbox')->version)];
        $package = str_replace($find, $replace, $package);
        $headers = array_merge(
            Output::getCacheHeaders(time(), 360),
            [
                'Content-Disposition'    => Output::getContentDisposition('attachment', 'package.json'),
                'X-Content-Type-Options' => 'nosniff',
            ]
        );

        /* Send headers and print file */
        Output::i()->sendStatusCodeHeader(200);
        Output::i()->sendHeader('Content-type: ' . File::getMimeType('package.json') . ';charset=UTF-8');

        foreach ($headers as $key => $header) {
            Output::i()->sendHeader($key . ': ' . $header);
        }

        Output::i()->sendHeader('Content-Length: ' . \strlen($package));

        print $package;
        exit;
    }
    /**
     * for segver compatibility
     *
     * @param $version
     *
     * @return string
     */
    public function buildVersion($version): string
    {
        $ver = explode('.', $version);
        $return = [];

        if (isset($ver[0])) {
            $return[] = $ver[0];
        }

        if (isset($ver[1])) {
            $return[] = $ver[1];
        } else {
            $return[] = 0;
        }

        if (isset($ver[2])) {
            $return[] = $ver[2];
        } else {
            $return[] = 0;
        }

        return implode('.', $return);
    }
    /**
     * builds the toolbox.js to download
     */
    protected function toolbox($values)
    {
        $url = DT_NODE_URL;
        preg_match('#\:[0-9]*$#',$url,$match);
        $toolbox = file_get_contents(\IPS\babble\Application::getRootPath('toolbox') . '/applications/toolbox/data/defaults/toolbox.txt');

        $find = [
            '#port#',
            '#sslkey#',
            '#sslcert#',
            '#sslbundle#',
        ];

        $replace = [
            str_replace(':','',$match[0]),
            $values['sslPrivateKey'],
            $values['sslCertificate'],
            $values['sslBundle'],
        ];

        $toolbox = str_replace($find, $replace, $toolbox);

        $headers = array_merge(
            Output::getCacheHeaders(time(), 360),
            [
                'Content-Disposition'    => Output::getContentDisposition('attachment', 'toolbox.js'),
                'X-Content-Type-Options' => 'nosniff',
            ]
        );

        /* Send headers and print file */
        Output::i()->sendStatusCodeHeader(200);
        Output::i()->sendHeader('Content-type: ' . File::getMimeType('toolbox.js') . ';charset=UTF-8');

        foreach ($headers as $key => $header) {
            Output::i()->sendHeader($key . ': ' . $header);
        }

        Output::i()->sendHeader('Content-Length: ' . \strlen($toolbox));

        print $toolbox;
        exit;
    }

    public function toolboxForm(){
        $form = Form::create();
        $form->addElement('sslPrivateKey')->label('SSL Private Key Path');
        $form->addElement('sslCertificate')->label('SSL Certificate');
        $form->addElement('sslBundle')->label('SSL Bundle');

        if($values = $form->values()){
            $this->toolbox($values);
        }

        Output::i()->output = $form;
    }


    public function node(){

        \IPS\Output::i()->sidebar['actions']['package'] = array(
            'icon' => '',
            'title'	=> 'Package.json',
            'link'	=> \IPS\Http\Url::internal( 'app=toolbox&module=settings&controller=settings&do=package' )
        );

        \IPS\Output::i()->sidebar['actions']['app'] = array(
            'icon' => '',
            'title'	=> 'Toolbox.js',
            'link'	=> \IPS\Http\Url::internal( 'app=toolbox&module=settings&controller=settings&do=toolboxForm' ),
            'data' => ['data-ipsDialog'=>1]
        );
    }
}
