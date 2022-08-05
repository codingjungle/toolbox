<?php

/**
 * @brief       Build Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.14
 * @version     -storm_version-
 */

namespace IPS\toolbox\Shared;

use IPS\Data\Store;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Application;
use IPS\toolbox\Build\Versions;
use IPS\toolbox\Form;

use function array_merge;
use function explode;
use function file_exists;
use function json_encode;
use function pathinfo;
use function preg_match;
use function ucfirst;

use const DT_MY_APPS;

trait Build
{
    protected function download()
    {
        $app = Request::i()->myApp;
        $data = Store::i()->dtversions;
        if (isset($data[$app])) {
            $values = $data[$app];
            $versions = (new Versions($app, $values));
            $download = $values['download'] ? true : false;
            $pharPath = $versions->build();
            unset($data[$app]);
            Store::i()->dtversions = $data;
            if ($download === true) {
                $output = \file_get_contents($pharPath);
                $pathInfo = pathinfo($pharPath);
                \Phar::unlinkArchive($pharPath);
                \IPS\Output::i()->sendOutput(
                    $output,
                    200,
                    'application/tar',
                    [
                        'Content-Disposition' => \IPS\Output::getContentDisposition('attachment', $pathInfo['basename'])
                    ],
                    false,
                    false,
                    false
                );
            } else {
                $title = $versions->app->_title;
                Member::loggedIn()->language()->parseOutputForDisplay($title);
                $data = json_encode(
                    [
                        'path' => $versions->path,
                        'error' => $versions->error,
                        'title' => $title
                    ]
                );
                \file_put_contents(Application::getRootPath('core') . '/dtDownloadData.json', $data);
                $url = Url::internal('app=toolbox&module=bt&controller=build')->setQueryString(
                    [
                        'do' => 'exported'
                    ]
                );
                Output::i()->redirect($url, 'Build Done');
            }
        }
    }

    protected function exported()
    {
        $file = Application::getRootPath('core') . '/dtDownloadData.json';
        if (file_exists($file)) {
            $export = json_decode(\file_get_contents($file), true);
            unlink($file);
            \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('bt', 'toolbox', 'front')->downloadInfo(
                $export['path'],
                $export['error'],
                $export['title']
            );
        } else {
            Output::i()->error('There is nothing to show!', '2DTB100/A');
        }
    }

    protected function form()
    {
        Output::i()->cssFiles = array_merge(Output::i()->cssFiles, Theme::i()->css('dtcode.css', 'toolbox', 'admin'));
        Output::i()->jsFiles = array_merge(
            Output::i()->jsFiles,
            Output::i()->js('admin_toggles.js', 'toolbox', 'admin')
        );
        $app = Request::i()->appToBuild;
        $form = Form::create()->submitLang('Build')->formClass('ipsBox ipsPadding');
        $myApps = \defined('DT_MY_APPS') ? explode(',', DT_MY_APPS) : [];
        if (empty($myApps) === false && \in_array($app, $myApps)) {
            $application = Application::load($app);
            if (empty($application->version) !== true) {
                $version = $application->version;
            } else {
                $version = '1.0.0-alpha.1';
            }
            preg_match(
                '#^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<preRelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildMetaData>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$#',
                $version,
                $matches
            );
            foreach ($matches as $key => $data) {
                if (!is_numeric($key)) {
                    ${$key} = $data;
                }
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
                    'This lets you select how you want to version bump y our applications long and short version (short version is also known as the human readable version, the long version is what IPS uses to do upgrades and to check for updates). Manual: allows you to increment the values on your own. Major, updates the major version number of the short version. Minor, updates only the minor version number. Patch, updates only the minor version number. <strong>Note: selecting major/minor/patch will increment your long version by 1, this will allow the IPS installer and update checker to work correctly. If your versioning number does not meet <a href="https://semver.org">SemVer.org</a> requirements, you should will need to select manual to correct any deficiencies in it and then do the build. On your next build, you will be able to use major/minor/patch option.</strong>'
                );

            $form->addElement('short')
                ->value($application->version)
                ->label('Short Version')
                ->description(
                    '<strong>MUST</strong> meet <a href="https://semver.org/">SemVer.org</a> Requirements.'
                )
                ->validation(static function ($data) {
                    $ba = 'build_app';
                    if ((int)Request::i()->{$ba} === 0 || !$data) {
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
                    if (!$data) {
                        return;
                    }
                    if ((int)$application->long_version > (int)$data) {
                        throw new \InvalidArgumentException(
                            'Your current long version, ' . $application->long_version . ', is greater than your next long version, ' . $data . '. This will make the app uninstallable, please correct.'
                        );
                    }
                });
            $pr = null;
            if (isset($preRelease)) {
                preg_match('#(.*?)\.(\d+)#', $preRelease, $matches);
                if (isset($matches[1])) {
                    $pr = 'is' . ucfirst(\mb_strtolower($matches[1]));
                }
            }
            $form->addElement('prerelease', 'radio')
                ->empty($pr)
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
            $form->addElement('analyze', 'yn')->empty(1)->label('Analyze')->description('Analyze App before building.');
            $form->addElement('slasher', 'yn')->label('Slasher')->description(
                'Use "Slasher" which will go thru and global namespace all php functions as use statements in your classes.'
            );
            $form->addElement('download', 'yn')->label('Download')->description(
                'Enable this to download instead of store to disk.'
            );
            /* @var \IPS\toolbox\Profiler\MyApps $extension */
            foreach ($application->extensions('toolbox', 'MyApps') as $extension) {
                $extension->addForm($form);
            }
//                } catch (Throwable $e) {
//                }
            if ($values = $form->values()) {
                $url = \IPS\Http\Url::internal('app=toolbox&module=bt&controller=build&myApp=' . $app);
                if (!isset($values['analyze']) || (isset($values['analyze']) && !$values['analyze'])) {
                    $url = $url->setQueryString(['do' => 'download']);
                } else {
                    $url = $url->setQueryString(['download' => 1, 'do' => 'queue']);
                }
                $versions = [];
                if (isset(Store::i()->dtversions)) {
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
}
