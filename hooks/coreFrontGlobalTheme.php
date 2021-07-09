//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */

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

    public function queryLog($querylog)
    {
            if (Dispatcher::hasInstance() && Dispatcher::i()->controllerLocation === 'admin' && Settings::i(
                )->dtprofiler_show_admin) {
                return;
            }
            $member = Member::loggedIn()->member_id;
            $can = json_decode(Settings::i()->dtprofiler_can_use, true);
            if (property_exists(
                    Output::i(),
                    'dtContentType'
                ) && Output::i()->dtContentType === 'text/html' && ((!IN_DEV && in_array(
                            $member,
                            $can,
                            true
                        )) || IN_DEV)) {
                try {
                    return Profiler::i()->run();
                } catch (Exception $e) {
                    throw $e;
                }
            }

        if ( \is_callable('parent::queryLog') )
        {
            return call_user_func_array('parent::' . __FUNCTION__, func_get_args() );
        }
    }

    public function cacheLog()
    {
            if (Dispatcher::hasInstance() && Dispatcher::i()->controllerLocation === 'admin' && Settings::i(
                )->dtprofiler_show_admin) {
                return;
            }
            $member = Member::loggedIn()->member_id;
            $can = json_decode(Settings::i()->dtprofiler_can_use, true);
            if (property_exists(
                    Output::i(),
                    'dtContentType'
                ) && Output::i()->dtContentType === 'text/html' && ((!IN_DEV && in_array(
                            $member,
                            $can,
                            true
                        )) || IN_DEV)) {
            } elseif (\is_callable('parent::cacheLog')) {
                return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
            }
    }
}
