//<?php


use IPS\Dispatcher;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\toolbox\Profiler;

use const IPS\IN_DEV;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    exit;
}

class toolbox_hook_coreFrontGlobalTheme extends _HOOK_CLASS_
{
    /* !Hook Data - DO NOT REMOVE */
    public static function hookData()
    {
        if (\is_callable('parent::hookData')) {
            return parent::hookData();
        }
        return [];
    }

    /* End Hook Data */

    public function globalTemplate($title,$html,$location=array()){
        $return = '';
        if (\is_callable('parent::globalTemplate')) {
            $return = \call_user_func_array('parent::' . __FUNCTION__, \func_get_args());
        }
        $member = Member::loggedIn()->member_id;
        $can = json_decode(Settings::i()->dtprofiler_can_use, true);
        $hide = defined('DT_HIDE_MYAPPS') ? DT_HIDE_MYAPPS : false;
        if (
            !$hide &&
            !\IPS\QUERY_LOG &&
            !\IPS\Request::i()->isAjax() &&
            property_exists(Output::i(), 'dtContentType') &&
            ((!IN_DEV && in_array($member, $can, true)) || IN_DEV)
        ) {
            try {
                $myapps = Profiler::i()->justMyApps();
                $return = str_replace('</body>', $myapps.'</body>', $return);
            } catch (Exception $e) {
                \IPS\toolbox\Profiler\Debug::log($e);
            }
        }

        return $return;
    }

    public function queryLog($querylog)
    {

        if (
            Dispatcher::hasInstance() &&
            Dispatcher::i()->controllerLocation === 'admin' &&
            Settings::i()->dtprofiler_show_admin
        ) {
            return;
        }
        $member = Member::loggedIn()->member_id;
        $can = json_decode(Settings::i()->dtprofiler_can_use, true);
        if (
            !\IPS\Request::i()->isAjax() &&
            property_exists(Output::i(), 'dtContentType') &&
            Output::i()->dtContentType === 'text/html' &&
            ((!IN_DEV && in_array($member, $can, true)) || IN_DEV)
        ) {
            try {
            } catch (Exception $e) {
                throw $e;
                \IPS\toolbox\Profiler\Debug::log($e);
            }
        }
        return Profiler::i()->run();

        return \is_callable('parent::queryLog') ? \call_user_func_array(
            'parent::' . __FUNCTION__,
            \func_get_args()
        ) : null;
    }

    public function cacheLog()
    {
        if (
            Dispatcher::hasInstance() &&
            Dispatcher::i()->controllerLocation === 'admin' &&
            Settings::i()->dtprofiler_show_admin
        ) {
            return;
        }
        $member = Member::loggedIn()->member_id;
        $can = json_decode(Settings::i()->dtprofiler_can_use, true);
        if (
            property_exists(Output::i(), 'dtContentType') &&
            Output::i()->dtContentType === 'text/html' &&
            (
                (
                    !IN_DEV &&
                    in_array($member, $can, true)
                ) ||
                IN_DEV
            )
        ) {
        } elseif (\is_callable('parent::cacheLog')) {
            return \call_user_func_array('parent::' . __FUNCTION__, \func_get_args());
        }
    }
}