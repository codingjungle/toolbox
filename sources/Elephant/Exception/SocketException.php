<?php

/**
 * @brief       SocketException Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Exception;

use Exception;
use RuntimeException;

class _SocketException extends RuntimeException
{
    public function __construct($errno, $error, Exception $previous = null)
    {
        parent::__construct(
            \sprintf(
                'There was an error while attempting to open a connection to the socket (Err #%d : %s)',
                $errno,
                $error
            ),
            $errno,
            $previous
        );
    }
}
