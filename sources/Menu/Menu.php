<?php

/**
 * @brief       Menu Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox;

use IPS\Application;
use IPS\Http\Url;
use IPS\Patterns\Singleton;
use IPS\Plugin;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use UnexpectedValueException;

use function _p;

use const DT_MY_APPS;

class _Menu extends Singleton
{

    /**
     * @inheritdoc
     */
    protected static $instance;

    /**
     * @return string
     * @throws UnexpectedValueException
     */
    public function build(): string
    {
        return Theme::i()->getTemplate('devBar', 'toolbox')->devBar($this->execute());
    }

    /**
     * add the menu to cache
     */
    public function execute(): array
    {
        $store = [];
        $store['roots']['toolbox'] = [
            'id'   => 'toolbox',
            'name' => 'Dev Toolbox',
            'url'  => 'elDevToolstoolbox',
        ];

        $store['toolbox'][] = [
            'id'   => 'settings',
            'name' => 'Settings',
            'url'  => (string)Url::internal('app=toolbox&module=settings&controller=settings'),
        ];
        $store['toolbox'][] = [
            'id'   => 'cons',
            'name' => 'Change Constants',
            'url'  => (string)Url::internal('app=toolbox&module=settings&controller=cons'),
        ];
        $store['toolbox'][] = [
            'id'   => 'proxy',
            'name' => 'Proxy Class Generator',
            'url'  => (string)Url::internal('app=toolbox&module=proxy&controller=proxy'),
        ];

        $store['toolbox'][] = [
            'id'   => 'code',
            'name' => 'Code Analyzer',
            'url'  => (string)Url::internal('app=toolbox&module=code&controller=analyzer'),
        ];
        /**
         * @var Application $app
         */
        foreach (Application::appsWithExtension('toolbox', 'menu') as $app) {
            /* @var \IPS\toolbox\extensions\toolbox\menu\menu $menu */
            foreach ($app->extensions('toolbox', 'menu', true) as $menu) {
                $menu->menu($store);
            }
        }

        $store['toolbox'][] = [
            'id'   => 'content',
            'name' => 'Content Generator',
            'url'  => (string)Url::internal('app=toolbox&module=content&controller=generator'),
        ];

        $store['toolbox'][] = [
            'id'   => 'DevFolder',
            'name' => 'Generate Application Dev Folder',
            'url'  => (string)Url::internal('app=toolbox&module=devfolder&controller=applications'),
        ];

        $this->menu($store);

        $store['roots'][] = [
            'id'   => 'apps',
            'name' => 'Apps',
            'url'  => 'elDevToolBoxApps',
        ];

        $applications = Application::applications();
        if( !Settings::i()->toolbox_use_tabs_applications ){
            foreach ($applications as $app) {
                $store['apps'][$app->directory] = [
                    'id' => $app->directory,
                    'name' => '__app_' . $app->directory,
                    'url' => (string)Url::internal(
                        'app=core&module=applications&controller=developer&appKey=' . $app->directory
                    ),
                ];
            }
        }
        else {
            $myapps = defined('DT_MY_APPS') ? explode(',', DT_MY_APPS) : [];
            if (empty($myapps) === false) {
                $myapps = array_combine(array_values($myapps),array_values($myapps));
                $store['apps']['myapps'] = [
                    'label' => 1,
                    'title' => 'My Apps',
                    'id' => 'myapps2'
                ];
                foreach ($applications as $app) {
                    if (isset($myapps[$app->directory])) {
                        $store['subs']['myapps2'][$app->directory] = [
                            'id' => $app->directory,
                            'name' => '__app_' . $app->directory,
                            'url' => (string)Url::internal(
                                'app=core&module=applications&controller=developer&appKey=' . $app->directory
                            ),
                        ];
                        unset($applications[$app->directory]);
                    }
                }
            }

            $store['apps']['core_sys'] = [
                'label' => 1,
                'title' => 'IPS Apps',
                'id' => 'core_sys2'
            ];
            foreach (\IPS\IPS::$ipsApps as $app) {
                if (isset($applications[$app])) {
                    $app = $applications[$app];
                    $store['subs']['core_sys2'][$app->directory] = [
                        'id' => $app->directory,
                        'name' => '__app_' . $app->directory,
                        'url' => (string)Url::internal(
                            'app=core&module=applications&controller=developer&appKey=' . $app->directory
                        ),
                    ];
                    unset($applications[$app->directory]);
                }
            }

            if (empty($applications) === false) {
                $store['apps']['3p'] = [
                    'label' => 1,
                    'title' => '3rd Party Apps',
                    'id' => '3p2'
                ];
                /**
                 * @var $apps Application
                 */
                foreach ($applications as $app) {
                    $store['subs']['3p2'][$app->directory] = [
                        'id' => $app->directory,
                        'name' => '__app_' . $app->directory,
                        'url' => (string)Url::internal(
                            'app=core&module=applications&controller=developer&appKey=' . $app->directory
                        ),
                    ];
                }
            }
        }
        $plugins = false;

        foreach (Plugin::plugins() as $plugin) {
            $plugins = true;
            $store['plugins'][$plugin->name] = [
                'id'   => $plugin->name,
                'name' => $plugin->name,
                'url'  => (string)Url::internal(
                    'app=core&module=applications&controller=plugins&do=developer&id=' . $plugin->id
                ),
            ];
        }

        if ($plugins) {
            $store['roots'][] = [
                'id' => 'plugins',
                'name' => 'Plugins',
                'url' => 'elDevToolsPlugins',
            ];
        }
        if (\IPS\Member::loggedIn()->hasAcpRestriction('toolbox', 'settings', 'settings_adminer')) {
            $store['roots'][] = [
                'id' => 'adminer',
                'name' => 'Adminer',
                'url' => (string)Url::internal('app=toolbox&module=settings&controller=adminer'),
                'subs' => false
            ];
        }
        return $store;
    }

    /**
     * default menu stuff
     *
     * @param $store
     */
    protected function menu(&$store)
    {
        $store['roots'][] = [
            'id'   => 'ips',
            'name' => 'IPS',
            'url'  => 'elDevToolboxIPS',
        ];

        $store['ips'][] = [
            'id'   => 'guides',
            'name' => 'Guides',
            'url'  => 'https://invisioncommunity.com/4guides/how-to-use-ips-community-suite/first-steps/terminology-r7/',
        ];

        $store['ips'][] = [
            'id'   => 'devdocs',
            'name' => 'Developer Documentation',
            'url'  => 'https://invisioncommunity.com/developers/',
        ];

        $store['ips'][] = [
            'id'   => 'comms',
            'name' => 'Community Forums',
            'url'  => 'https://invisioncommunity.com/forums/forum/503-customization-resources/',
        ];

        $store['ips'][] = [
            'id'   => 'notes',
            'name' => 'Release Notes',
            'url'  => 'https://invisioncommunity.com/release-notes/',
        ];

        $store['roots'][] = [
            'id'   => 'sys',
            'name' => 'System',
            'url'  => 'elDevToolboxsys',
        ];

        $store['sys'][] = [
            'id'   => 'apps',
            'name' => 'Applications',
            'url'  => (string)Url::internal('app=core&module=applications&controller=applications'),
        ];

        $store['sys'][] = [
            'id'   => 'plugins',
            'name' => 'Plugins',
            'url'  => (string)Url::internal('app=core&module=applications&controller=plugins'),
        ];
        $store['sys'][] = [
            'id'   => 'api',
            'name' => 'API',
            'url'  => (string)Url::internal('app=core&module=applications&controller=api')
        ];
        $store['sys'][] = [
            'id'   => 'logs',
            'name' => 'Logs',
            'url'  => (string)Url::internal('app=core&module=support&controller=systemLogs'),
        ];

        $store['sys'][] = [
            'id'   => 'task',
            'name' => 'Tasks',
            'url'  => (string)Url::internal('app=core&module=settings&controller=advanced&do=tasks'),
        ];

        $store['sys'][] = [
            'id'   => 'sql',
            'name' => 'SQL Toolbox',
            'url'  => (string)Url::internal('app=core&module=support&controller=sql'),
        ];

        $store['sys'][] = [
            'id'   => 'support',
            'name' => 'Support',
            'url'  => (string)Url::internal('app=core&module=support&controller=support'),
        ];

        $store['sys'][] = [
            'id'   => 'error',
            'name' => 'Error Logs',
            'url'  => (string)Url::internal('app=core&module=support&controller=errorLogs'),
        ];

        $store['sys'][] = [
            'id' => 'syscheck',
            'name' => 'System Check',
            'url' => (string)Url::internal('app=core&module=support&controller=support&do=systemCheck'),
        ];

        $store['sys'][] = [
            'id' => 'phpinfo',
            'name' => 'PHP Info',
            'url' => (string)Url::internal('app=core&module=support&controller=support&do=phpinfo'),
        ];
    }
}
