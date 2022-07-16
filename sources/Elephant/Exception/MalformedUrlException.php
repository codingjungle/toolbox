<?php

/**
 * @brief       MalformedUrlException Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Exception;

use Exception;
use InvalidArgumentException;

class _MalformedUrlException extends InvalidArgumentException
{
    public function __construct($url, Exception $previous = null)
    {
        parent::__construct(\sprintf('The url "%s" seems to be malformed', $url), 0, $previous);
    }
}
