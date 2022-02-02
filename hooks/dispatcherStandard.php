//<?php namespace toolbox_IPS_Dispatcher_Standard_aadac28b89813071422b6e80864852e77;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Widget;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class toolbox_hook_dispatcherStandard extends _HOOK_CLASS_
{
    public function run()
    {
        Widget::deleteCaches();
        parent::run();
    }
}
