<?php

/**
* @brief      ApiException Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.1
* @version    -storm_version-
*/

namespace IPS\toolbox\Api;

use Exception;
use Throwable;
use IPS\stratagem\Member;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
* ApiException Class
* @mixin \IPS\toolbox\Api\ApiException
*/
class _ApiException extends Exception
{
    protected $ecode;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (Member::loggedIn()->language()->checkKeyExists($message)) {
            $message = Member::loggedIn()->language()->addToStack($message);
            Member::loggedIn()->language()->parseOutputForDisplay($message);
        }
        parent::__construct($message, 0, $previous);
        $this->code = $code;
    }
}
