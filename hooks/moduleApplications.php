//<?php namespace toolbox_IPS_core_modules_admin_applications_applications_aa1a93e0b9169cbfa98b32190845b4cb5;

use DirectoryIterator;
use IPS\Application;
use IPS\Db;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\toolbox\Build;

use function array_keys;
use function array_merge;
use function defined;
use function explode;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_dir;
use function json_decode;

use const DT_MY_APPS;
use const DTBUILD;
use const IPS\CIC2;
use const IPS\SITE_FILES_PATH;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class toolbox_hook_moduleApplications extends _HOOK_CLASS_
{

    /**
     * Export an application
     *
     *
     * @return void
     * @note    We have to use a custom RecursiveDirectoryIterator in order to skip the /dev folder
     */
    public function download()
    {
        if (!isset(Request::i()->analyzed) && defined('\DT_ANALYZE') && DT_ANALYZE) {
            $url = \IPS\Http\Url::internal('app=toolbox&module=code&controller=analyzer')->setQueryString([
                'do' => 'queue',
                'application' => Request::i()->appKey,
                'download' => 1
            ]);
            Output::i()->redirect($url);
        }
        if (defined('\DTBUILD') && DTBUILD) {
            Build::i()->export();
        } else {
            parent::download();
        }
    }

    protected function manage()
    {
        if( !Settings::i()->toolbox_use_tabs_applications ||  Request::i()->isAjax()){
            parent::manage();;
        }
        else {
            $output = '';
            if (!\IPS\Request::i()->isAjax()) {
                if (\IPS\IPS::canManageResources()) {
                    if (\IPS\IPS::checkThirdParty()) {
                        $output = \IPS\Theme::i()->getTemplate('forms')->blurb('applications_blurb');
                    } else {
                        $output = \IPS\Theme::i()->getTemplate('forms')->blurb('applications_blurb_no_custom');
                    }
                } else {
                    $output = \IPS\Theme::i()->getTemplate('forms')->blurb('applications_blurb_no_upload');
                }
            }
            $un = null;
            $ips = null;
            $mine = null;
            $third = null;
            /* Javascript */
            Output::i()->jsFiles = array_merge(
                Output::i()->jsFiles,
                Output::i()->js('admin_system.js', 'core', 'admin')
            );

            /* Check for updates button */
            Output::i()->sidebar['actions']['settings'] = array(
                'icon' => 'refresh',
                'link' => \IPS\Http\Url::internal(
                    'app=core&module=applications&controller=applications&do=updateCheck'
                )->csrf(),
                'title' => 'check_for_updates',
            );
            if (\IPS\IN_DEV) {
                Output::i()->sidebar['actions']['build_all'] = array(
                    'icon' => 'cogs',
                    'link' => \IPS\Http\Url::internal(
                        'app=core&module=applications&controller=applications&do=buildAll'
                    ),
                    'title' => 'build_all_apps',
                    'data' => array(
                        'ipsDialog' => '',
                        'ipsDialog-title' => Member::loggedIn()->language()->addToStack('build_all_apps')
                    )
                );
            }

            $uninstalled = array();
            $installed = array_keys(Application::applications());

            foreach (new DirectoryIterator(\IPS\ROOT_PATH . "/applications/") as $file) {
                if ($file->isDir() and !in_array($file->getFilename(), $installed) and !$file->isDot()) {
                    if (file_exists($file->getPathname() . '/data/application.json')) {
                        $application = json_decode(
                            file_get_contents($file->getPathname() . '/data/application.json'),
                            true
                        );

                        $uninstalled[$file->getFilename()] = array(
                            'title' => $application['application_title'],
                            'author' => $application['app_author'],
                            'website' => $application['app_website'],
                        );
                    }
                }
            }
            if (CIC2) {
                if (is_dir(SITE_FILES_PATH . "/applications/")) {
                    foreach (new DirectoryIterator(SITE_FILES_PATH . "/applications/") as $file) {
                        if ($file->isDir() and !in_array($file->getFilename(), $installed) and !$file->isDot()) {
                            if (file_exists($file->getPathname() . '/data/application.json')) {
                                $application = json_decode(
                                    file_get_contents($file->getPathname() . '/data/application.json'),
                                    true
                                );

                                $uninstalled[$file->getFilename()] = array(
                                    'title' => $application['application_title'],
                                    'author' => $application['app_author'],
                                    'website' => $application['app_website'],
                                );
                            }
                        }
                    }
                }
            }
            //my apps
            if (defined('DT_MY_APPS')) {
                $mine = new \IPS\Helpers\Tree\Tree(
                    $this->url,
                    Application::$nodeTitle,
                    array($this, '_myAppRoots'),
                    array($this, '_getRow'),
                    array($this, '_getRowParentId'),
                    array($this, '_getChildren'),
                    array($this, '_getRootButtons'),
                    true,
                    $this->lockParents,
                    $this->protectRoots
                );
            }
            //3rd party
            if (empty($this->_thirdPartyRoots()) === false) {
                $third = new \IPS\Helpers\Tree\Tree(
                    $this->url,
                    Application::$nodeTitle,
                    array($this, '_thirdPartyRoots'),
                    array($this, '_getRow'),
                    array($this, '_getRowParentId'),
                    array($this, '_getChildren'),
                    array($this, '_getRootButtons'),
                    true,
                    $this->lockParents,
                    $this->protectRoots
                );
            }
            //ips
            if (Application::$databaseColumnParent === null) {
                $this->protectRoots = true;
            }

            $ips = new \IPS\Helpers\Tree\Tree(
                $this->url,
                Application::$nodeTitle,
                array($this, '_getRoots'),
                array($this, '_getRow'),
                array($this, '_getRowParentId'),
                array($this, '_getChildren'),
                array($this, '_getRootButtons'),
                true,
                $this->lockParents,
                $this->protectRoots
            );

            //uninstalled
            if (\count($uninstalled) and empty(\IPS\Request::i()->root)) {
                $baseUrl = $this->url;
                $tree = new \IPS\Helpers\Tree\Tree(
                    $this->url,
                    \IPS\Member::loggedIn()->language()->addToStack('uninstalled_applications'),
                    function () use ($uninstalled, $baseUrl) {
                        $rows = array();

                        if (!empty($uninstalled) and \is_array($uninstalled)) {
                            foreach ($uninstalled as $k => $app) {
                                $buttons = array();
                                if (\IPS\IPS::canManageResources()) {
                                    $buttons = array(
                                        'add' => array(
                                            'icon' => 'plus-circle',
                                            'title' => 'install',
                                            'link' => \IPS\Http\Url::internal(
                                                "app=core&module=applications&controller=applications&appKey={$k}&do=install"
                                            )->csrf(),
                                        )
                                    );
                                }

                                $rows[$k] = \IPS\Theme::i()->getTemplate('trees')->row(
                                    $baseUrl,
                                    $k,
                                    $app['title'],
                                    false,
                                    $buttons
                                );
                            }
                        }
                        return $rows;
                    },
                    function ($key, $root = false) use ($uninstalled, $baseUrl) {
                        $buttons = array();
                        if (\IPS\IPS::canManageResources() and \IPS\IPS::checkThirdParty()) {
                            $buttons = array(
                                'add' => array(
                                    'icon' => 'plus-circle',
                                    'title' => 'install',
                                    'link' => \IPS\Http\Url::internal(
                                        "app=core&module=applications&controller=applications&appKey={$key}&do=install"
                                    )->csrf(),
                                )
                            );
                        }

                        return \IPS\Theme::i()->getTemplate('trees')->row(
                            $baseUrl,
                            $key,
                            $uninstalled[$key]['title'],
                            false,
                            $buttons,
                            '',
                            null,
                            null,
                            $root
                        );
                    },
                    function () {
                        return 0;
                    },
                    function () {
                        return array();
                    },
                    function () {
                        return array();
                    },
                    false,
                    true,
                    true
                );

                $un = \IPS\Theme::i()->getTemplate('applications')->applicationWrapper(
                    $tree,
                    'uninstalled_applications'
                );
            }
            Output::i()->output = Theme::i()->getTemplate('application', 'toolbox', 'admin')->wrapper(
                $output,
                $mine,
                $ips,
                $third,
                $un
            );
        }
    }

    /**
     * Get Root Rows
     *
     * @return	array
     */
    public function _getRoots()
    {
        if( !Settings::i()->toolbox_use_tabs_applications){
            return parent::_getRoots();
        }
        $rows = array();
        $ipsApps = \IPS\IPS::$ipsApps;
        $sql = Db::i()->select('*','core_applications',Db::i()->in('app_directory', $ipsApps), 'app_position ASC');
        $roots = new ActiveRecordIterator($sql, Application::class);
        foreach( $roots as $node )
        {
            $rows[ $node->_id ] = $this->_getRow( $node );
        }

        return $rows;
    }

    public function _myAppRoots(){
        $rows = array();
        $myApps = explode(',',DT_MY_APPS);
        $sql = Db::i()->select('*','core_applications',Db::i()->in('app_directory', $myApps), 'app_position ASC');
        $roots = new ActiveRecordIterator($sql, Application::class);
        foreach( $roots as $node )
        {
            $rows[ $node->_id ] = $this->_getRow( $node );
        }

        return $rows;
    }

    public function _thirdPartyRoots(){
        $rows = array();
        $apps = \IPS\IPS::$ipsApps;
        if(defined('DT_MY_APPS')) {
            $myApps = explode(',', DT_MY_APPS);
            $apps = array_merge($apps,$myApps);
        }
        $sql = Db::i()->select('*','core_applications',Db::i()->in('app_directory', $apps,true), 'app_position ASC');
        $roots = new ActiveRecordIterator($sql, Application::class);
        foreach( $roots as $node )
        {
            $rows[ $node->_id ] = $this->_getRow( $node );
        }

        return $rows;
    }
}
