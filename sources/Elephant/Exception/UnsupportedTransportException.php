<?php

/**
 * @brief       UnsupportedTransportException Class
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

class _UnsupportedTransportException extends RuntimeException
{
    public function __construct($transport, Exception $previous = null)
    {
        parent::__construct(
            \sprintf('This server does not support the %s transport, aborting', $transport),
            0,
            $previous
        );
    }
}
