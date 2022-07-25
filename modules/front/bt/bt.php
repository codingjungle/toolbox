<?php

/**
 * @brief       Bt Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\modules\front\bt;

use Exception;
use http\Url;
use Intervention\Image\ImageManager;
use IPS\Application\BuilderIterator;
use IPS\calendar\Date;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Output\Cache;
use IPS\Plugin;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Application;
use IPS\toolbox\Build\Versions;
use IPS\toolbox\Form;
use IPS\toolbox\Profiler;
use IPS\toolbox\Profiler\Debug;
use IPS\toolbox\Proxy\Generator\Proxy;
use IPS\toolbox\Proxy\Proxyclass;
use IPS\toolbox\Shared\Lorem;
use IPS\toolbox\Shared\Uuid;
use IPS\toolbox\Slasher;
use Phar;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use UnexpectedValueException;

use function _p;
use function array_pop;
use function base64_decode;
use function count;
use function defined;
use function header;
use function htmlentities;
use function implode;
use function ini_get;
use function is_array;
use function is_dir;
use function mb_strtolower;
use function mb_strtoupper;
use function md5;
use function microtime;
use function mt_rand;
use function nl2br;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function pathinfo;
use function phpinfo;
use function pow;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function sleep;
use function str_replace;
use function time;
use function trim;
use function uniqid;

use const DT_MY_APPS;
use const DT_SLASHER;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\NO_WRITES;


Application::loadAutoLoader();

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * bt
 */
class _bt extends Controller
{

    public function dates()
    {
        $time = Request::i()->time ?? Date::create()->getTimestamp();
        $type = Request::i()->type ?? 'unix';
        $dates = Profiler\Dates::i()->{$type}($time);
        if (isset(Request::i()->time)) {
            Output::i()->json($dates);
        } else {
            Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->dates($dates);
        }
    }

    /**
     * @inheritdoc
     */
    protected function manage(): void
    {
        $store = Store::i()->dtprofiler_bt;
        $hash = Request::i()->bt;
        $output = 'Nothing Found';
        if (isset($store[$hash])) {
            $bt = str_replace("\\\\", "\\", $store[$hash]['bt']);
            $output = '<code>' . $store[$hash]['query'] . '</code><br><pre class="prettyprint lang-php">' . $bt . '</pre>';
        }

        Output::i()->output = "<div class='ipsPad'>{$output}</div>";
    }

    /**
     * shows data for the cache dialog
     */
    protected function cache(): void
    {
        $store = Store::i()->dtprofiler_bt_cache;
        $hash = Request::i()->bt;
        $output = 'Nothing Found';
        if (isset($store[$hash])) {
            $bt = str_replace("\\\\", "\\", $store[$hash]['bt']);
            $content = nl2br(htmlentities($store[$hash]['content']));
            $output = '<code>' . $content . '</code><br><pre class="prettyprint lang-php">' . $bt . '</pre>';
        }

        Output::i()->output = "<div class='ipsPad'>{$output}</div>";
    }

    /**
     * @throws Db\Exception
     * @throws UnexpectedValueException
     */
    protected function debug(): void
    {
        $max = (ini_get('max_execution_time') / 2) - 5;
        $time = time();
        $since = Request::i()->last ?: 0;
        while (true) {
            $ct = time() - $time;
            if ($ct >= $max) {
                Output::i()->json(['error' => 1]);
            }

            $config = [
                'where' => [
                    'debug_id > ? AND debug_viewed = ?',
                    $since,
                    0,
                ]
            ];
            $debug = Debug::all($config, true);
            if ($debug !== 0) {
                $debug = Debug::all($config);
                $last = 0;
                $list = [];
                /* @var Debug $obj */
                foreach ($debug as $obj) {
                    $list[] = $obj->body();
                    $last = $obj->id;
                }

                $return = [];
                if (empty($list) !== true) {
                    $count = count($list);
                    $return['count'] = $count;
                    $lists = '';
                    foreach ($list as $l) {
                        $lists .= Theme::i()->getTemplate('generic', 'toolbox', 'front')->li($l);
                    }
                    $return['last'] = $last;
                    $return['items'] = $lists;
                }

                if (is_array($return) && count($return)) {
                    Output::i()->json($return);
                }
            } else {
                sleep(1);
                continue;
            }
        }
    }

    protected function phpinfo(): void
    {
        ob_start();
        phpinfo();
        $content = ob_get_clean();
        try {
            ob_end_clean();
        } catch (Exception $e) {
        }
        $content = preg_replace('#<head>(?:.|\n|\r)+?</head>#miu', '', $content);
        Output::i()->title = 'phpinfo()';
        Output::i()->output = Theme::i()->getTemplate('bt', 'toolbox', 'front')->phpinfo($content);
    }

    protected function clearCaches(): void
    {
        $redirect = base64_decode(Request::i()->data);
        $this->_clearCache();
        Output::i()->redirect($redirect);
    }

    protected function _clearCache()
    {
        $path = \IPS\Application::getRootPath() . '/hook_temp';

        if (is_dir($path)) {
            Application::loadAutoLoader();
            $fs = new Filesystem();
            $fs->remove([$path]);
        }
        /* Don't clear CSS/JS when we click "check again" or the page will be broken - it's unnecessary anyways */
        if (!isset(Request::i()->checkAgain)) {
            /* Clear JS Maps first */
            Output::clearJsFiles();

            /* Reset theme maps to make sure bad data hasn't been cached by visits mid-setup */
            Theme::deleteCompiledCss();
            Theme::deleteCompiledResources();

            foreach (Theme::themes() as $id => $set) {
                /* Invalidate template disk cache */
                $set->cache_key = md5(microtime() . mt_rand(0, 1000));
                $set->save();
            }
        }

        Store::i()->clearAll();
        \IPS\Data\Cache::i()->clearAll();
        Cache::i()->clearAll();

        Member::clearCreateMenu();
    }

    protected function thirdParty(): void
    {
        $enable = (int)Request::i()->enabled;
        $redirect = base64_decode(Request::i()->data);
        $apps = Profiler::i()->apps();
        $plugins = Profiler::i()->plugins();

        /* Loop Apps */
        foreach ($apps as $app) {
            Db::i()->update('core_applications', ['app_enabled' => $enable], ['app_id=?', $app->id]);
        }

        /* Look Plugins */
        foreach ($plugins as $plugin) {
            Db::i()->update('core_plugins', ['plugin_enabled' => $enable], ['plugin_id=?', $plugin->id]);
        }

        if (!empty($apps)) {
            Application::postToggleEnable();
        }

        if (!empty($plugins)) {
            Plugin::postToggleEnable(true);
        }

        /* Clear cache */
        Cache::i()->clearAll();
        Plugin\Hook::writeDataFile();
        Output::i()->redirect($redirect);
    }

    protected function enableDisableApp(): void
    {
        $enabled = (int)Request::i()->enabled;
        if ($enabled === 1) {
            $enabled = 0;
        } else {
            $enabled = 1;
        }
        $redirect = base64_decode(Request::i()->data);
        $id = (int)Request::i()->id;
        $data = Db::i()->select('*', 'core_applications', ['app_id=?', $id])->first();
        /** @var Application $app */
        $app = \IPS\Application::constructFromData($data);
        $app->enabled = $enabled;
        $app->save();
//        Db::i()->update('core_applications', ['app_enabled' => $enabled], ['app_id=?', $id]);
        Application::postToggleEnable();
        $this->_clearCache();

        Output::i()->redirect($redirect);
    }

    protected function enableDisablePlugin(): void
    {
        $enabled = !Request::i()->enabled;
        $redirect = base64_decode(Request::i()->data);
        $id = Request::i()->id;
        Db::i()->update('core_plugins', ['plugin_enabled' => $enabled], ['plugin_id=?', $id]);
        Application::postToggleEnable();
        Cache::i()->clearAll();
        Output::i()->redirect($redirect);
    }

    protected function gitInfo(): void
    {
        $info = [];
        Profiler::i()->getLastCommitId($info);
        Profiler::i()->hasChanges($info);
        $html = '';
        if (!empty($info)) {
            $html = Theme::i()->getTemplate('bar', 'toolbox', 'front')->git($info);
        }

        Output::i()->json(['html' => $html]);
    }

    protected function gitCheckout()
    {
    }

    protected function lorem(): void
    {
        $form = Form::create()->formPrefix('toolbox_lorem_')->submitLang(null)->attributes(
            ['data-ipstoolboxtoyboxlorem' => 1]
        );

        $form->addElement('amount', 'number')->value(4)->options(['min' => 1]);
        $form->addElement('type', 'radio')
            ->value(3)
            ->options(
                [
                    'options' => [
                        1 => 'Words',
                        2 => 'Sentences',
                        3 => 'Paragraphs',
                    ],
                ]
            )->required();

        if ($values = $form->values()) {
            $return = '';
            $amount = $values['amount'];
            switch ($values['type']) {
                case 1:
                    $return = Lorem::i()->words($amount);
                    break;
                case 2:
                    $return = Lorem::i()->sentences($amount, ['p']);
                    break;
                case 3:
                    $return = Lorem::i()->paragraphs($amount, ['p']);
                    break;
            }

            Output::i()->json(['html' => $return, 'type' => 'toolboxClipBoard']);
        }
        $form->dialogForm();
        Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->lorem(
            $form,
            Lorem::i()->paragraphs(4, ['p'])
        );
    }

    protected function base()
    {
        Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->base();
    }

    protected function bitwiseValues()
    {
        $position = Request::i()->position ?? 15;
        $form = '';
        $html = '<div class="ipsPadding ipsClearfix" id="elBitWiseBox"><div class="ipsPos_left ipsMargin_right">';
//        $html .= '<div>1 => 1,</div>';
        for ($i = 1; $i <= $position; $i++) {
            $start = pow(2, $i - 1);
            if (($i - 1) % 15 === 0) {
                $html .= '</div><div class="ipsPos_left ipsMargin_right">';
            }
            $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
            $html .= '<div>\'' . $f->format($i) . '\' => ' . $start . ',</div>';
        }
        $html .= '</div></div>';

        if (!Request::i()->position) {
            $form = Form::create()->submitLang(null)->attributes(['data-ipstoolboxtoyboxbitwise' => 1]);
            $form->addElement('position', 'number')->value(15)->options(['min' => 15]);
        }
        Output::i()->output = $form . $html;
    }

    protected function hash()
    {
        $html = '';
        if (!Request::i()->hash) {
            $html .= '<div class="ipsPadding"><textarea>Hello World</textarea></div>';
        }
        $html .= '<div class="ipsPadding" id="elHashWindow">';
        $hash = Request::i()->hash ?? 'Hello World';
        $md5 = md5($hash);
        $sha1 = sha1($hash);
        $sha256 = hash('sha256', $hash);
        $sha512 = hash('sha512', $hash);
        Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->hash(
            $hash,
            $md5,
            $sha1,
            $sha256,
            $sha512
        );
    }

    protected function uuid()
    {
        $count = Request::i()->count ?? 3;
        $hyphens = Request::i()->hyphens ?? true;
        $lowercase = Request::i()->lowercase ?? false;
        $html = [];


        $form = Form::create()->attributes(['data-ipstoolboxtoyboxuuid' => null])->submitLang(null);
        $form->addElement('count', 'number')->value(3)->options(['min' => 1]);
        $form->addElement('hyphens', 'yesno')->value(1);
        $form->addElement('lowercase', 'yesno');
        if ($values = $form->values()) {
            $form = '';
            $count = (int)$values['count'];
            $hyphens = (bool)$values['hyphens'];
            $lowercase = (bool)$values['lowercase'];
        }

        for ($i = 1; $i <= $count; $i++) {
            $hash = Uuid::v4();
            if ($hyphens === false) {
                $hash = str_replace('-', '', $hash);
            }
            if ($lowercase === true) {
                $hash = mb_strtolower($hash);
            } else {
                $hash = mb_strtoupper($hash);
            }
            $html[] = $hash;
        }

        if ($form instanceof Form) {
            Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->uuid(
                $form,
                implode('<br>', $html)
            );
        } else {
            Output::i()->output = '<br>' . implode('<br>', $html);
        }
    }

    protected function html()
    {
        $encoded = $decoded = '<a href="#foo">link</a>';

        Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->html($decoded, $encoded);
    }

    protected function numbers()
    {
        $number = Request::i()->number ?? 3456;
        $type = Request::i()->type ?? 'decimal';
        try {
            $output = Profiler\Numbers::i()->{$type}($number);
        } catch (\InvalidArgumentException $e) {
            $output = [
                $type => $number,
                'error' => $e->getMessage()
            ];
        }
        if (isset(Request::i()->type)) {
            Output::i()->json($output);
        } else {
            Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->numbers($output);
        }
    }

    protected function diffs()
    {
        Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->diffs();
    }

    protected function images()
    {
        $form = Form::create()->formPrefix('dtprofilerImagesConverter_')->submitLang(null);
        $options = [
            'storageExtension' => 'toolbox_FileStorage',
            'storageContainer' => 'toolboxConverter',
            'allowedFileTypes' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'),
        ];
        $form->addElement('images', 'upload')->options($options)->required();
        $options = [
            'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'tif' => 'tif',
            'bmp' => 'bmp',
            'ico' => 'ico',
            'psd' => 'psd',
            'webp' => 'webp'
        ];
        ksort($options);
        $form->addElement('to', 'select')->options(['options' => $options])->value('png');

        if ($values = $form->values()) {
            Application::loadAutoLoader();
            $config = [
                'driver' => \extension_loaded('imagick') ? 'imagick' : 'gd'
            ];
            $manager = new ImageManager($config);
            /** @var \IPS\File $file */
            $file = $values['images'];
            $img = (string)$manager->make($file->url)->encode($values['to']);

            $newFile = File::create(
                'toolbox_FileStorage',
                'imageConverted-' . $values['to'] . '-' . uniqid() . '.' . $values['to'],
                $img,
                'toolboxConverter',
                true
            );
            $file->delete();
            $newFile->save();
            Output::i()->json(['path' => (string)$newFile, 'url' => $newFile->url]);
        }

        $form->dialogForm();
        Output::i()->output = Theme::i()->getTemplate('bar', 'toolbox', 'front')->images($form);
    }

    protected function download()
    {
        $path = Request::i()->path;
        $info = pathinfo($path);
        $file = File::get('toolbox_FileStorage', $path);
        $contents = $file->contents(true);
        $name = $file->originalFilename;
        $file->delete();
        Output::i()->sendOutput(
            $contents,
            200,
            'image/' . $info['extension'],
            [
                'Content-Disposition' => Output::getContentDisposition(
                    'attachment',
                    $name
                ),
            ]
        );
    }

    protected function clearAjax()
    {
        Db::i()->update('toolbox_debug', ['debug_viewed' => 1]);
    }

    protected function proxy()
    {
        try {
            if (NO_WRITES === true) {
                Output::i()->error(
                    'Proxy generator can not be used atm, NO_WRITES is enabled in the constants.php.',
                    '100foo'
                );
            }
            Proxyclass::i()->dirIterator();
            Proxyclass::i()->buildHooks();
            $iterator = Store::i()->dtproxy_proxy_files;
            foreach ($iterator as $key => $file) {
                Proxyclass::i()->build($file);
            }
            unset(Store::i()->dtproxy_proxy_files);
            Proxy::i()->buildConstants();
            $step = 1;
            do {
                $step = Proxyclass::i()->makeToolboxMeta($step);
            } while ($step !== null);
            Proxy::i()->generateSettings();
            Proxyclass::i()->buildCss();
            unset(Store::i()->dtproxy_proxy_files, Store::i()->dtproxy_templates);
            Output::i()->output = '';
        } catch (Throwable $e) {
            Debug::log($e);
            if (Request::i()->isAjax()) {
                Output::i()->json($e->getMessage() . '<br><code>' . $e->getTraceAsString() . '</code>', 500);
            } else {
                throw $e;
            }
        }
    }

    protected function build()
    {
        $app = Request::i()->appToBuild;
        $form = Form::create()->submitLang('Build')->formClass('ipsBox ipsPadding');
        $myApps = \defined('DT_MY_APPS') ? explode(',', DT_MY_APPS) : [];
        if (empty($myApps) === false && \in_array($app,$myApps) ) {

//                try {
                    $application = Application::load($app);
                    if (empty($application->version) !== true) {
                        $version = $application->version;
                    } else {
                        $version = '1.0.0';
                    }
                    Member::loggedIn()->language()->words[$app . '_header'] = \mb_strtoupper(
                            $app
                        ) . ' Version: ' . $version;
                    $form->header($app);


                    $form->addElement('bumpType', 'radio')
                        ->options(
                            [
                                'options' => [
                                    'manual' => 'Manual',
                                    'major' => 'Major',
                                    'minor' => 'Minor',
                                    'patch' => 'Patch',
                                ]
                            ]
                        )
                        ->toggles(
                            [
                                'manual' => [
                                    'short',
                                    'long'
                                ]
                            ]
                        )
                        ->validation(static function ($data) use ($application) {
                            if ($data !== 'manual') {
                                preg_match(
                                    '#^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$#',
                                    $application->version,
                                    $matches
                                );
                                if (empty($matches)) {
                                    throw new \InvalidArgumentException(
                                        'Your short version does not meet the SemVer.org requirements. example <(int)major>.<(int)minor>.<(int)patch>(-alpha|beta|rc.<(int)prerelease build>, eg 3.1.4-beta.12 or 3.1.4. Please select "manual" and correct it.'
                                    );
                                }
                            }
                        })
                        ->label('Version Bump')
                        ->description(
                            'This lets you select how you want to version bump your applications long and short version (short version is also known as the human readable version, the long version is what IPS uses to do upgrades and to check for updates). Manual: allows you to increment the values on your own. Major, updates the major version number of the short version. Minor, updates only the minor version number. Patch, updates only the minor version number. <strong>Note: selecting major/minor/patch will increment your long version by 1, this will allow the IPS installer and update checker to work correctly. If your versioning number does not meet <a href="https://semver.org">SemVer.org</a> requirements, you should will need to select manual to correct any deficiencies in it and then do the build. On your next build, you will be able to use major/minor/patch option.</strong>'
                        );

                    $form->addElement('slasher','yn')->label('Slasher')->description('Use "Slasher" which will go thru and global namespace all php functions as use statements in your classes.');
                    $form->addElement('analyze','yn')->label('Analyze')->description('Analyze App before building.');
                    $form->addElement('short')
                        ->value($application->version)
                        ->label('Short Version')
                        ->description(
                            '<strong>MUST</strong> meet <a href="https://semver.org/">SemVer.org</a> Requirements.'
                        )
                        ->validation(static function ($data) {

                            $ba = 'build_app';
                            if((int) Request::i()->{$ba} === 0 || !$data){
                                return;
                            }
                            preg_match(
                                '#^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$#',
                                $data,
                                $matches
                            );

                            if (empty($matches) === true) {
                                throw new \InvalidArgumentException(
                                    'Your short version does not meet the SemVer.org standards. example <(int)major>.<(int)minor>.<(int)patch>(-alpha|beta|rc.<(int)prerelease build>, eg 3.1.4-beta.12 or 3.1.4'
                                );
                            }
                        });
                    $form->addElement('long', 'number')
                        ->value($application->long_version)
                        ->label('Long Version')
                        ->description(
                            'Your long version should be a string of 5 to 6 digits (possibly more or less if you weren\'t following semantic version before now). Your next long version should be at least 1 increment bigger than the current long version. If your current version is 10010 your next version should be either 10100 or 10011. If your current long version is less than 5 digits, you should consider increasing it to 10000 at least having the first number match your current major version. if your current version is 3.1.4, your major version number is 3, so you should set your long version number, if less than 5 characters, to 30000. <strong>Note: if your next long version is less than your current long version, then the IPS upgrader will spaz out and cause your app being rejected.</strong>'
                        )
                        ->validation(static function ($data) use ($application) {
                            if(!$data){
                                return;
                            }
                            if ((int)$application->long_version > (int) $data) {
                                throw new \InvalidArgumentException(
                                    'Your current long version, ' . $application->long_version . ', is greater than your next long version, ' . $data . '. This will make the app uninstallable, please correct.'
                                );
                            }
                        });
                    $form->addElement('prerelease', 'radio')
                        ->options(
                            [
                                'options' => [
                                    null => 'None',
                                    'isAlpha' => 'Alpha',
                                    'isBeta' => 'Beta',
                                    'isRc' => 'Release Candidate'
                                ]
                            ]
                        )
                        ->label('Pre-Release Versioning')
                        ->description(
                            'If this is pre release software, you should select what stage you are at in the development. if this is the first release of it, -alpha|beta|rc.1 will be appended to your short version, other wise it will increment it if the previous short version is the same stage, so -alpha.1 will become -alpha.2, but if you move to beta from alpha, it will then become -beta.1. selecting none, will not append any pre-release versioning to your short version, and it will remove it from your next version if major/minor/patch options are used.'
                        );
                    /* @var \IPS\toolbox\Profiler\MyApps $extension */
                    foreach ($application->extensions('toolbox', 'MyApps') as $extension) {
                        $extension->addForm($form);
                    }
//                } catch (Throwable $e) {
//                }
            if ($values = $form->values()) {
                $url = \IPS\Http\Url::internal('app=toolbox&module=bt&controller=build&myApp='.$app);
                if(!isset($values['analyze']) || (isset($values['analyze']) && !$values['analyze'])){
                    $url = $url->setQueryString(['do' => 'download' ]);
                }
                else{
                    $url = $url->setQueryString(['download'=>1,'do'=>'queue']);
                }
                $versions = [];
                if(isset(Store::i()->dtversions)) {
                    $versions = Store::i()->dtversions;
                }
                $versions[$app] = $values;
                Store::i()->dtversions = $versions;
                Output::i()->redirect($url);
            }
        }
        $form->dialogForm();
        Output::i()->output = $form;
    }

    /**
     * shows data for the logs dialog
     */
    protected function log(): void
    {
        $id = Request::i()->id;
        $output = 'Nothing Found';
        try {
            $log = Log::load($id);
            $data = DateTime::ts($log->time);
            $name = 'Date: ' . $data;
            if ($log->category !== null) {
                $name .= '<br> Type: ' . $log->category;
            }

            if ($log->url !== null) {
                $name .= '<br> URL: ' . $log->url;
            }
            $msg = nl2br(htmlentities($log->message));
            $output = $name . '<br>' . $msg . '<br><pre class="prettyprint lang-php">' . $log->backtrace . '</pre>';
        } catch (Exception $e) {
        }

        Output::i()->output = "<div class='ipsPad'>{$output}</div>";
    }

//    protected function adminer()
//    {
//        $url = Url::baseUrl() . '/applications/toolbox/sources/Profiler/Adminer/db.php';
//        Output::i()->output = '<iframe id="toolboxAdminer"  width="100%" height="600px" src="' . $url . '"></iframe>';
//    }

    //    protected function checkout(){
    //        $app = Request::i()->dir;
    //        $branch = Request::i()->branch;
    //        $redirect = \base64_decode(Request::i()->data);
    //        $path = \IPS\Application::getRootPath().'/applications/'.$app.'/.git/';
    //        if( is_dir( $path ) && function_exists( 'exec' ) ){
    ////            try {
    //                $git = new GitRepository($path);
    //                $git->checkout( $branch );
    ////            } catch (GitException $e) {
    ////            }
    //        }
    //        Output::i()->redirect($redirect);
    //    }

    //    protected function commitPush()
    //    {
    //        $app = Request::i()->dir;
    //        $branch = Request::i()->branch;
    //        $redirect = \base64_decode(Request::i()->data);
    //        $gitReposPath = \IPS\Application::getRootPath() . '/git.php';
    //        $appRepos = [];
    //        if (file_exists($gitReposPath)) {
    //            require $gitReposPath;
    //            if( isset( $appRepos[$app] ) ){
    //            $path = \IPS\Application::getRootPath() . '/applications/' . $app . '/.git/';
    //            if (is_dir($path) && function_exists('exec')) {
    //                $e[] = [
    //                    'class' => 'textarea',
    //                    'name' => 'dtprofiler_commit_message'
    //                ];
    //
    //                $e[] = [
    //                    'class' => 'yn',
    //                    'name' => 'dtprofiler_push'
    //                ];
    //
    //                $forms = Forms::execute(['elements' => $e, 'submitLang' => 'dtprofiler_commit_button']);
    //
    //                if ($values = $forms->values()) {
    //                    $msg = $values[ 'dtprofiler_commit_message' ];
    //                    //                try {
    //                    $git = new GitRepository($path);
    //                    $git->execute( [
    //                        'config',
    //                        'user.name',
    //                        Member::loggedIn()->name
    //                    ]);
    //
    //                    $git->execute( [
    //                        'config',
    //                        'user.email',
    //                        Member::loggedIn()->email
    //                    ]);
    ////                    $git->addAllChanges();
    //                    $git->commit($msg, '-a');
    //                    //git config --get remote.origin.url
    //
    //                    if ($values[ 'dtprofiler_push' ]) {
    //                        foreach( $appRepos[$app] as $repo ) {
    //                            $git->push(null, ['--repo' => $repo]);
    //                        }
    //                    }
    //                    //                } catch (GitException $e) {
    //                    //                }
    //                    Output::i()->redirect($redirect);
    //
    //                }
    //            }
    //                Output::i()->output = $forms;
    //            }
    //        }
    //        else{
    //            Output::i()->redirect($redirect);
    //        }
    //    }
}
