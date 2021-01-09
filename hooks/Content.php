//<?php namespace toolbox_IPS_Content_a6d0a5877dcb9bf2e6ba437d5b3ab1a02;

/* To prevent PHP errors (extending class does not exist) revealing path */

use const TOOLBOXDEV;

if ( !\defined('\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class toolbox_hook_Content extends _HOOK_CLASS_
{

    public function modAction($action, Member $member = null, $reason = null, $immediately = false)
    {
        if( defined( 'TOOLBOXDEV') && TOOLBOXDEV ){
            $immediately = true;
        }
        return parent::modAction($action, $member, $reason, $immediately); // TODO: Change the autogenerated stub
    }
}