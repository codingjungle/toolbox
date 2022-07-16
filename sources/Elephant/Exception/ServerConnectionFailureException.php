<?php

/**
 * @brief       ServerConnectionFailureException Class
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

class _ServerConnectionFailureException extends RuntimeException
{
    /** @var string php error message */
    protected $errorMessage;

    public function __construct($errorMessage, Exception $previous = null)
    {
        parent::__construct(
            \sprintf('An error occurred while trying to establish a connection to the server, %s', $errorMessage),
            0,
            $previous
        );

        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
