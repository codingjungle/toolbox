//<?php namespace toolbox_IPS_Log_a282b50cced3eb66f7d56a23302804425;

use IPS\toolbox\Profiler\Debug;

use function defined;

use const DT_ROUTE_TO_DEBUG;
use const IN_DEV;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class toolbox_hook_ipsLog extends _HOOK_CLASS_
{

    public static function debug($message, $category = null)
    {

        if(IN_DEV === true && $category === 'content_debug'){
            throw $message;
        }
        if ($category === 'request') {
            return;
        }
        if(defined('DT_ROUTE_TO_DEBUG') && DT_ROUTE_TO_DEBUG === true){
            Debug::log($message, $category);
        }
        return parent::debug($message, $category);
    }
//
    public static function log($message, $category = null)
    {
        if(defined('DT_ROUTE_TO_DEBUG') && DT_ROUTE_TO_DEBUG === true){
            Debug::log($message, $category);

        }
        return parent::log($message, $category);
    }
}
