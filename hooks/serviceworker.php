//<?php namespace toolbox_IPS_core_modules_front_system_serviceworker_a9e344f094551338ce24c598335b05085;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Output;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class toolbox_hook_serviceworker extends _HOOK_CLASS_
{

    protected function manage()
    {
        if(defined('DT_DISABLE_SERVICE_WORKERS') && DT_DISABLE_SERVICE_WORKERS === false) {
            parent::manage();
        }
        else{

            Output::i()->sendOutput('', 200, 'text/javascript', []);
        }
    }
}
