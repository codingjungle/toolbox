<?php

/**
 * @brief       Toolbox Constants extension: Toolbox
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\extensions\toolbox\constants;

use IPS\IPS;

use function defined;
use function header;

use function property_exists;

use const DT_ANALYZE;
use const DT_BETA_AUTHOR;
use const DT_BETA_CATEGORY;
use const DT_BETA_CLIENT_ID;
use const DT_BETA_CLIENT_SECRET;
use const DT_BETA_UPLOAD;
use const DT_BETA_URL;
use const DT_DISABLE_SERVICE_WORKERS;
use const DT_MY_APPS;
use const DT_NODE_URL;
use const DT_ROUTE_TO_DEBUG;
use const DT_THEME;
use const DT_THEME_CMS_USE_DESIGNER_FILES;
use const DT_THEME_ID;
use const DT_THEME_ID_ADMIN;
use const DTBUILD;
use const DTPROFILER;
use const TOOLBOXDEV;
use const TOOLBOXDEV_IMMEDIATE;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * toolbox
 */
class _toolbox
{

    /**
     * add in array of constants
     */
    public function getConstants()
    {
        $return = [
            'DT_ANALYZE'                         => [
                'name'        => 'DT_ANALYZE',
                'default'     => false,
                'current'     => defined('\DT_ANALYZE') ? DT_ANALYZE : null,
                'description' => 'This enables an app to be analyzed before you download it.',
                'type'        => 'boolean',
                'tab'         => 'DevTools',
            ],
            'DTBUILD'                         => [
                'name'        => 'DTBUILD',
                'default'     => false,
                'current'     => defined('\DTBUILD') ? DTBUILD : null,
                'description' => 'This enables special app build features for toolbox, use with caution.',
                'type'        => 'boolean',
                'tab'         => 'DevTools',
            ],
            'DT_THEME'                        => [
                'name'        => 'DT_THEME',
                'default'     => false,
                'current'     => defined('\DT_THEME') ? DT_THEME : false,
                'description' => 'this will enable/disable designer mode templates to be used with IN_DEV. check out the HowToUseDesignerDevMode.txt.',
                'type'        => 'boolean',
                'tab'         => 'DevTools',
            ],
            'DT_THEME_ID'                     => [
                'name'        => 'DT_THEME_ID',
                'default'     => 0,
                'current'     => defined('\DT_THEME_ID') ? DT_THEME_ID : 0,
                'description' => 'enter the theme ID number to use.',
                'type'        => 'int',
                'tab'         => 'DevTools',
            ],
            'DT_THEME_ID_ADMIN'               => [
                'name'        => 'DT_THEME_ID_ADMIN',
                'default'     => 0,
                'current'     => defined('\DT_THEME_ID_ADMIN') ? DT_THEME_ID_ADMIN : 0,
                'description' => 'if you want to use a different theme for the ACP than on the front end, enter theme ID number here, leave 0 to keep disabled.',
                'type'        => 'int',
                'tab'         => 'DevTools',
            ],
            'DT_THEME_CMS_USE_DESIGNER_FILES' => [
                'name'        => 'DT_THEME_CMS_USE_DESIGNER_FILES',
                'default'     => 0,
                'current'     => defined('\DT_THEME_CMS_USE_DESIGNER_FILES') ? DT_THEME_CMS_USE_DESIGNER_FILES : false,
                'description' => 'use the designer mode templates.',
                'type'        => 'boolean',
                'tab'         => 'DevTools',

            ],
            'DT_ANALYZE'               => [
                'name'        => 'DT_ANALYZE',
                'default'     => 0,
                'current'     => defined('\DT_ANALYZE') ? DT_ANALYZE : false,
                'description' => 'Runs code analyzer on export of app.',
                'type'        => 'boolean',
                'tab'         => 'DevTools'
            ],
            'DT_SLASHER' => [
                'name' => 'DT_SLASHER',
                'default' => 0,
                'current' => defined('\DT_SLASHER') ? DT_SLASHER : false,
                'description' => 'combined with DTBUILD, enables or disables the slasher routine (slasher adds in the global namespace to php functions thru imports).',
                'type' => 'boolean',
                'tab' => 'DevTools',
            ],
            'DT_MY_APPS'                         => [
                'name'        => 'DT_MY_APPS',
                'default'     => false,
                'current'     => defined('\DT_MY_APPS') ? DT_MY_APPS : "adminer,babble,babbleadmin,babbleextra,chrono,cjcboard,cjdashboard,cjdml,cjgames,cjmember,cjmg,cjrates,cjseo,cjtrack,cjtwd,clubmenus,dgform,dplus,dwlabs,formularize,keywords,myimports,n2a,nettookit,stratagem,toolbox,toplist",
                'description' => 'This enables an app to be analyzed before you download it.',
                'type' => 'string',
                'tab'         => 'DevTools',
            ],
            'TOOLBOXDEV_IMMEDIATE'               => [
                'name'        => 'TOOLBOXDEV_IMMEDIATE',
                'default'     => 0,
                'current'     => defined('\TOOLBOXDEV_IMMEDIATE') ? TOOLBOXDEV_IMMEDIATE : false,
                'description' => 'sets delete to be immediate instead of going to the log. this is helpful if you are testing things.',
                'type'        => 'boolean',
                'tab'         => 'Debug'
            ],
            'DTPROFILER'                      => [
                'name'        => 'DTPROFILER',
                'default'     => false,
                'current'     => defined('\DTPROFILER') ? DTPROFILER : null,
                'description' => 'this will enable/disable any profiler debug/time class to use the debug/time features of profiler.',
                'type'        => 'boolean',
                'tab'         => 'Debug',
            ],
            'TOOLBOXDEV'                      => [
                'name'        => 'TOOLBOXDEV',
                'default'     => false,
                'current'     => defined('\TOOLBOXDEV') ? TOOLBOXDEV : false,
                'description' => 'this will enable/disable extra features for toolbox..',
                'type'        => 'boolean',
                'tab'         => 'Debug',

            ],
            'DT_DISABLE_SERVICE_WORKERS'      => [
                'name'        => 'DT_DISABLE_SERVICE_WORKERS',
                'default'     => 0,
                'current'     => defined('\DT_DISABLE_SERVICE_WORKERS') ? DT_DISABLE_SERVICE_WORKERS : false,
                'description' => 'disable the annoying service worker content in the console log.',
                'type'        => 'boolean',
                'tab'         => 'Debug',
            ],
            'DT_ROUTE_TO_DEBUG'               => [
                'name'        => 'DT_ROUTE_TO_DEBUG',
                'default'     => 0,
                'current'     => defined('\DT_ROUTE_TO_DEBUG') ? DT_ROUTE_TO_DEBUG : false,
                'description' => 'Pushes IPS logs to the profiler debugger.',
                'type'        => 'boolean',
                'tab'         => 'Debug'
            ],
            'DT_NODE'                         => [
                'name'        => 'DT_NODE',
                'default'     => false,
                'current'     => defined('\DT_NODE') ? DT_NODE : false,
                'description' => 'Enable debug to use node.js instead of long polling.',
                'type' => 'boolean',
                'tab'         => 'Debug',
            ],
            'DT_NODE_URL'                         => [
                'name'        => 'DT_NODE_URL',
                'default'     => false,
                'current'     => defined('\DT_NODE_URL') ? DT_NODE_URL : 'http://localhost:3010',
                'description' => 'Url with port for node.js',
                'type' => 'url',
                'tab'         => 'Debug',
            ],
            'DT_USE_WSL' => [
                'name'        => 'DT_USE_WSL',
                'default'     => false,
                'current'     => defined('\DT_USE_WSL') ? DT_USE_WSL : false,
                'description' => 'Are we using WSL?',
                'type' => 'boolean',
                'tab'         => 'Debug',
            ],'DT_WSL_PATH' => [
                'name'        => 'DT_WSL_PATH',
                'default'     => '\\\\wsl.localhost\\Ubuntu',
                'current'     => defined('\DT_WSL_PATH') ? DT_WSL_PATH : '\\\\wsl.localhost\\Ubuntu',
                'description' => 'Path to wsl, this most likely will not be changed, unless you use another distro.',
                'type' => 'string',
                'tab'         => 'Debug',
            ],
            'DT_USE_CONTAINER' => [
                'name'        => 'DT_USE_CONTAINER',
                'default'     => false,
                'current'     => defined('\DT_USE_CONTAINER') ? DT_USE_CONTAINER : false,
                'description' => 'Are we using a container like docker/devilbox/etc?',
                'type' => 'boolean',
                'tab'         => 'Debug',
            ],
            'DT_CONTAINER_GUEST_PATH' => [
                'name'        => 'DT_CONTAINER_GUEST_PATH',
                'default'     => \IPS\ROOT_PATH,
                'current'     => defined('\DT_CONTAINER_GUEST_PATH') ? DT_CONTAINER_GUEST_PATH : \IPS\ROOT_PATH,
                'description' => 'This is the part of the path to remove, this is usually the DOCUMENT ROOT path.',
                'type' => 'string',
                'tab'         => 'Debug',
            ],
            'DT_CONTAINER_HOST_PATH' => [
                'name'        => 'DT_CONTAINER_HOST_PATH',
                'default'     => '/home/michael/devilbox/data/www/ips/htdocs',
                'current'     => defined('\DT_CONTAINER_PATH') ? DT_CONTAINER_HOST_PATH : '/home/michael/devilbox/data/www/ips/htdocs',
                'description' => 'Path to files on disk, this will be something like /home/michael/devilbox/data/www/ips/htdocs.',
                'type' => 'string',
                'tab'         => 'Debug',
            ],
            'DT_BETA_UPLOAD'                         => [
                'name'        => 'DT_BETA_UPLOAD',
                'default'     => false,
                'current'     => defined('\DT_BETA_UPLOAD') ? DT_BETA_UPLOAD : FALSE,
                'description' => 'Special task to upload files when DTBUILDS is used.',
                'type' => 'boolean',
                'tab'         => 'Beta',
            ],
            'DT_BETA_CATEGORY'                         => [
                'name'        => 'DT_BETA_CATEGORY',
                'default'     => 0,
                'current'     => defined('\DT_BETA_CATEGORY') ? DT_BETA_CATEGORY : 1,
                'description' => 'Category ID to use.',
                'type' => 'int',
                'tab'         => 'Beta',
            ],
            'DT_BETA_AUTHOR'                         => [
                'name'        => 'DT_BETA_AUTHOR',
                'default'     => 0,
                'current'     => defined('\DT_BETA_AUTHOR') ? DT_BETA_AUTHOR : 1,
                'description' => 'Member ID to use.',
                'type' => 'int',
                'tab'         => 'Beta',
            ],
            'DT_BETA_URL'                         => [
                'name'        => 'DT_BETA_URL',
                'default'     => 0,
                'current'     => defined('\DT_BETA_URL') ? DT_BETA_URL : null,
                'description' => 'url to use.',
                'type' => 'url',
                'tab'         => 'Beta',
            ],
            'DT_BETA_CLIENT_ID'                         => [
                'name'        => 'DT_BETA_CLIENT_ID',
                'default'     => 0,
                'current'     => defined('\DT_BETA_CLIENT_ID') ? DT_BETA_CLIENT_ID : null,
                'description' => 'OAUTH client id.',
                'type' => 'string',
                'tab'         => 'Beta',
            ],

            'DT_BETA_CLIENT_SECRET'                         => [
                'name'        => 'DT_BETA_CLIENT_SECRET',
                'default'     => 0,
                'current'     => defined('\DT_BETA_CLIENT_SECRET') ? DT_BETA_CLIENT_SECRET : null,
                'description' => 'OAUTH client secret.',
                'type' => 'string',
                'tab'         => 'Beta',
            ],
            'DT_BETA_ALLOWED'                         => [
                'name'        => 'DT_BETA_ALLOWED',
                'default'     => 0,
                'current'     => defined('\DT_BETA_ALLOWED') ? DT_BETA_ALLOWED : null,
                'description' => 'A comma separated list of app directory names to allow to be uploaded if they don\'t have beta/rc as apart of their name.',
                'type' => 'string',
                'tab'         => 'Beta',
            ],
            'DT_BETA_DISALLOWED'                         => [
                'name'        => 'DT_BETA_DISALLOWED',
                'default'     => 0,
                'current'     => defined('\DT_BETA_DISALLOWED') ? DT_BETA_DISALLOWED : null,
                'description' => 'List of app directory names to completely ignore and not upload',
                'type' => 'string',
                'tab'         => 'Beta',
            ],
        ];

        if(\IPS\QUERY_LOG === false){
            $return['DT_HIDE_MYAPPS'] = [
                'name'        => 'DT_HIDE_MYAPPS',
                'default'     => 0,
                'current'     => defined('\DT_HIDE_MYAPPS') ? DT_HIDE_MYAPPS : false,
                'description' => 'Hides the "my apps/dev toolbox when query log is disabled.',
                'type' => 'boolean',
                'tab'         => 'DevTools',
            ];
        }


        return $return;
    }

    /**
     * define an array of constant names to add to the important tab
     *
     * @return array
     */
    public function add2Important()
    {
        return [
            'DEV_DISABLE_ACP_SESSION_TIMEOUT',
            'BYPASS_ACP_IP_CHECK',
            'IN_DEV',
            'IN_DEV_STRICT_MODE',
            'USE_DEVELOPMENT_BUILDS',
            'DEV_WHOOPS_EDITOR',
            'DEV_DEBUG_JS',
            'QUERY_LOG',
            'COOKIE_PREFIX',
            'CP_DIRECTORY',
            'DEV_USE_WHOOPS',
            'DEV_HIDE_DEV_TOOLS',
            'DEV_DEBUG_CSS',
            'DEBUG_TEMPLATES',
            'DEBUG_LOG',
            'COOKIE_PATH',
        ];
    }

    /**
     * formValues, format the values before saving as settings
     *
     * @param array $values
     *
     * @return void
     */
    public function formateValues(&$values)
    {
    }
}
