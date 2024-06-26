//<?php namespace toolbox_IPS_Content_a6d0a5877dcb9bf2e6ba437d5b3ab1a02;

/* To prevent PHP errors (extending class does not exist) revealing path */

use const TOOLBOXDEV_IMMEDIATE;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    exit;
}

abstract class toolbox_hook_Content extends _HOOK_CLASS_
{

	public function modAction( $action, \IPS\Member $member = NULL, $reason = NULL, $immediately = FALSE )
    {
        if (defined('TOOLBOXDEV_IMMEDIATE') && TOOLBOXDEV_IMMEDIATE) {
            $immediately = true;
        }
        return parent::modAction($action, $member, $reason, $immediately);
    }
}
