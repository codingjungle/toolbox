//<?php namespace toolbox_IPS_Session_a43df255efcbd66963cea6bbb25c7aa6a;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class toolbox_hook_Session extends _HOOK_CLASS_
{
    public static function sessionLifetime()
    {
        if(\IPS\DEV_DISABLE_ACP_SESSION_TIMEOUT){
            return 630720000;
        }
        return parent::sessionLifetime();
    }
}
