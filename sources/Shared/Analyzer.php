<?php

/**
 * @brief       Analyzer Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.8
 * @version     -storm_version-
 */


namespace IPS\toolbox\Shared;

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Dispatcher\Admin;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Code\ClassScanner;
use IPS\toolbox\Code\Db;
use IPS\toolbox\Code\ErrorCodes;
use IPS\toolbox\Code\FileStorage;
use IPS\toolbox\Code\Hooks;
use IPS\toolbox\Code\InterfaceFolder;
use IPS\toolbox\Code\Langs;
use IPS\toolbox\Code\RootPath;
use IPS\toolbox\Code\Settings;
use IPS\toolbox\Code\Utils\Hook;
use IPS\toolbox\Editor;
use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;

use function array_merge;
use function defined;
use function ksort;
use function round;

trait Analyzer
{


    /**
     * @inheritdoc
     */
    protected function manage()
    {
        $form = \IPS\toolbox\Form::create();
        foreach (Application::applications() as $key => $val) {
            if (!defined('DTCODE_NO_SKIP') && in_array($val->directory, IPS::$ipsApps, true)) {
                continue;
            }
            $apps[$val->directory] = Member::loggedIn()->language()->addToStack("__app_{$val->directory}");
        }

        ksort($apps);

        $form->addElement('dtcode_app', 'select')
            ->options(['options'=>$apps])
            ->required();

        if ($values = $form->values()) {
            Output::i()->redirect(
                $this->url->setQueryString(
                    [
                        'do'          => 'queue',
                        'application' => $values['dtcode_app'],
                    ]
                )
            );
        }

        Output::i()->output = $form;
    }

    /**
     *
     * @throws Exception
     */
    protected function queue()
    {
        $application = Request::i()->myApp ?? Request::i()->application;
        $download = Request::i()->download ?? 0;

        if($download !== 0){
            $baseUrl = Url::internal('app=toolbox&module=bt&controller=build')
                ->setQueryString(
                    [
                        'myApp' => $application,
                        'download' => $download
                    ]
                );
            $url = $baseUrl->setQueryString(['do'=>'queue']);
            $redirect = $url->setQueryString(['do'=>'results']);
        }
        else{
            $url = Url::internal('app=toolbox&module=code&controller=analyzer&do=queue&download=1')
                ->setQueryString(
                    [
                        'application' => $application,
                        'download'    => $download
                    ]
                );

            $redirect = Url::internal('app=toolbox&module=code&controller=analyzer&do=results')->setQueryString([
                'application' => $application,
                'download'    => $download
            ]);
        }

        Output::i()->output = new MultipleRedirect(
            $url,
            function ($data) use( $url, $application ) {
            $total = 11;
            $percent = round(100 / $total);
            $complete = 0;
            $app = $application;
            if (is_array($data) && isset($data['complete'])) {
                $app = (string)$data['app'];
                $complete = (int)$data['complete'];
            }

            $warnings = [];

            if ($complete !== 0 && isset(Store::i()->dtcode_warnings)) {
                $warnings = Store::i()->dtcode_warnings;
            }

            switch ($complete) {
                case 0:
                    if(\IPS\Settings::i()->dtcode_analyze_db) {
                        $warnings['sql_check'] = (new Db($app))->check();
                    }
                    break;
                case 1:
                    if(\IPS\Settings::i()->dtcode_analyze_filestorage) {
                        $warnings['filestorage_check'] = (new FileStorage($app))->check();
                    }
                    break;
                case 2:
                    if(\IPS\Settings::i()->dtcode_analyze_rootpath) {
                        $warnings['root_path'] = (new RootPath($app))->check();
                    }
                    break;
                case 3:
                    if(\IPS\Settings::i()->dtcode_analyze_error_codes) {
                        $errorsCodes = (new ErrorCodes($app))->check();
                        if (empty($errorsCodes['warnings']) === false) {
                            $warnings['error_codes_ips'] = $errorsCodes['warnings'];
                        }
                        if (empty($errorsCodes['dupes']) === false) {
                            $warnings['error_codes_dupes'] = $errorsCodes['dupes'];
                        }
                    }
                    break;
                case 4:
                    if(\IPS\Settings::i()->dtcode_analyze_hooks) {
                        try {
                            $hooks = new Hooks($app);
                            $warnings['hooks_exists'] = $hooks->exist();
                            $warnings['hooks_validation'] = $hooks->validate();
                        } catch (\InvalidArgumentException $e) {
                        }
                    }
                    break;
                case 5:
                    if(\IPS\Settings::i()->dtcode_analyze_interface) {
                        $warnings['interface_occupied'] = (new InterfaceFolder($app))->check();
                    }
                    break;
                case 6:
                    if(\IPS\Settings::i()->dtcode_analyze_settings_verify) {
                        $warnings['settings_verify'] = (new Settings($app))->buildSettings()->verify();
                    }
                    break;
                case 7:
                    if(\IPS\Settings::i()->dtcode_analyze_settings_check) {
                        $warnings['settings_check'] = (new Settings($app))->buildSettings()->check();
                    }
                    break;
                case 8:
                    if(\IPS\Settings::i()->dtcode_analyze_langs_check) {
                        $warnings['langs_check'] = (new Langs($app))->check();
                    }
                    break;
                case 9:
                    if(\IPS\Settings::i()->dtcode_analyze_langs_verify) {
                        $warnings['langs_verify'] = (new Langs($app))->verify();
                    }
                    break;
                case 10:
                    if(\IPS\Settings::i()->dtcode_analyze_class_scanner) {
                        $warnings['class_scanner_validation'] = (new ClassScanner($app))->validate();
                    }
                    break;
            }
                $complete++;

                Store::i()->dtcode_warnings = $warnings;

            if ($complete > $total) {
                return null;
            }

            $language = Member::loggedIn()->language()->addToStack(
                'dtcode_queue_complete',
                false,
                [
                    'sprintf' => [
                        $complete,
                        $total,
                    ],
                ]
            );

            return [
                ['complete' => $complete, 'app' => $app],
                $language,
                $percent * $complete,
            ];
        },
            function () use ($redirect) {
                Output::i()->redirect($redirect, 'dtcode_analyzer_complete');
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     * @throws UnexpectedValueException
     */
    protected function results()
    {
        $application = Request::i()->myApp ?? Request::i()->application;
        $app = Application::load($application);
        $title = 'dtcode_results';
        $options = [];
        if ($app !== null) {
            $title = 'dtcode_results_app';
            $options = ['sprintf' => [Member::loggedIn()->language()->addToStack('__app_' . $app->directory)]];
        }

        Output::i()->title = Member::loggedIn()->language()->addToStack($title, false, $options);

        if (isset(Store::i()->dtcode_warnings)) {
            /**
             * @var array $warnings
             */
            $stored = Store::i()->dtcode_warnings;
            $warnings = [];
            if(isset($stored['class_scanner_validation'])){
                $warnings['class_scanner_validation'] = $stored['class_scanner_validation'];
                unset($stored['class_scanner_validation']);
            }

            if(isset($stored['hooks_exists'])){
                $warnings['hooks_exists'] = $stored['hooks_exists'];
                unset($stored['hooks_exists']);
            }

            if(isset($stored['hooks_validation'])){
                $warnings['hooks_validation'] = $stored['hooks_validation'];
                unset($stored['hooks_validation']);
            }

            foreach($stored as $key => $value){
                $warnings[$key] = $value;
            }

            $output = '';
            foreach ($warnings as $key => $val) {
                switch ($key) {
                    case 'langs_check':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val['langs'] ?? [],
                            'dtcode_langs_php',
                            [],
                            true
                        );

                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val['jslangs'] ?? [],
                            'dtcode_jslangs_php',
                            [],
                            true
                        );
                        break;
                    case 'langs_verify':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'dtcode_langs_verify',
                            [
                                'File',
                                'Key',
                                'Line'
                            ],
                            true
                        );
                        break;
                    case 'settings_check':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'dtcode_settings_check',
                        );
                        break;
                    case 'settings_verify':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'dtcode_settings_verify'
                        );
                        break;
                    case 'filestorage_check':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'filestorage_check',
                            [
                                'Extension',
                                'Error Message'
                            ]
                        );
                        break;
                    case 'sql_check':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'dtcode_sql',
                            [
                                'File',
                                'Application',
                                'Table',
                                'Definition'
                            ]
                        );
                        break;
                    case 'interface_occupied':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'interface_occupied',
                            [
                                'File'
                            ],
                            true
                        );
                        break;
                    case 'root_path':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'root_path',
                            [
                                'File',
                                'Key',
                                'Line'
                            ]
                        );
                        break;
                    case 'error_codes_ips':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'error_codes_ips',
                            [
                                'File',
                                'Key',
                                'Line',
                                'IPS Locations'
                            ]
                        );
                        break;
                    case 'error_codes_dupes':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                            $val,
                            'error_codes_dupes',
                            [
                                'File',
                                'Key',
                                'Line'
                            ]
                        );
                        break;
                    case 'hooks_exists':
                        $output .= Theme::i()->getTemplate('code','toolbox','admin')->hooksExists(
                            $val,
                            'hooks_exists',
                            [
                                'File',
                                'Path',
                            ]
                        );
                        break;
                    case 'hooks_validation':
                        $parse = $val['parse'] ?? null;
                        $loading = $value['processing'] ?? null;
                        $count = 0;

                        $op = '';
                        foreach($val as $k => $data){

                            $count += count($data);
                            $headers = [];
                            if($k === 'signature' || $k === 'parameters'){
                                $headers[] = 'File';
                            }
                            $headers[] = 'Path';
                            $headers[] = 'Error';
                            $headers[] = 'Line';
                            $op .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                                $data,
                                'hooks_'.$k,
                                [
                                    'File',
                                    'Error',
                                    'Line'
                                ]
                            );
                        }

                        $output .= Theme::i()
                            ->getTemplate('code','toolbox','admin')
                            ->resultsBlock($key.'_title',$count, $op);
                        break;
                    case 'class_scanner_validation':
                        $op = '';
                        $count = 0;
                        foreach($val as $k => $data){
                            $count += count($data);
                            if($k === 'processing'){
                                $headers = [
                                    'Error',
                                    'Path'
                                ];
                            }
                            elseif($k === 'case'){
                                $headers = [
                                    'Error',
                                    'Path',
                                    'Class'
                                ];
                            }
                            else{
                                $headers = [
                                    'Error',
                                    'Path',
                                    'Line',
                                    'Method',
                                ];
                            }
                            $op .= Theme::i()->getTemplate('code','toolbox','admin')->results(
                                $data,
                                'class_scanner_'.$k,
                                $headers
                            );
                        }
                        $output .= Theme::i()
                            ->getTemplate('code','toolbox','admin')
                            ->resultsBlock($key.'_title',$count, $op);
                        break;
                }
            }
            $download = Request::i()->download ?? 0;

                $baseUrl = Url::internal('app=toolbox&module=bt&controller=build')
                    ->setQueryString(
                        [
                            'myApp'=>$application,
                            'download'=> $download
                        ]
                    );
                $analyze = $baseUrl->setQueryString(['do'=>'queue']);
                $downloadUrl = $baseUrl->setQueryString(['do'=>'download']);
            Output::i()->output = Theme::i()->getTemplate('code','toolbox','admin')->final(
                $output,
                (bool)$download,
                $downloadUrl,
                $analyze
            );
        }
    }

    protected function glitch(){
        $application = Request::i()->myApp ?? Request::i()->application;
        $app = Application::load($application);
        $info = Store::i()->toolbox_code_analyzer_interrupted;
        $curl = Request::i()->url();
        $url = (new Editor())->replace($info['file'], $info['line']);
        $name = str_replace($app->getApplicationPath(),'',$info['file']);
        $analyze = $curl->setQueryString(['download' => 1,'do'=>'queue']);
        if(isset(Request::i()->application)){
            $analyze = $analyze->stripQueryString(['application'])->setQueryString(['myApp'=>$application]);
        }
        Output::i()->output = Theme::i()
            ->getTemplate('code','toolbox','admin')
            ->glitch(
                'E_COMPILE_ERROR',
                $url,
                $name,
                $info['message'],
                $analyze
            );
    }
}
