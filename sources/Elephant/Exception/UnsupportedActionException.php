<?php

/**
 * @brief       UnsupportedActionException Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Exception;

use BadMethodCallException;
use Exception;
use IPS\toolbox\Elephant\EngineInterface;

class _UnsupportedActionException extends BadMethodCallException
{
    public function __construct(EngineInterface $engine, $action, Exception $previous = null)
    {
        parent::__construct(
            \sprintf('The action "%s" is not supported by the engine "%s"', $engine->getName(), $action),
            0,
            $previous
        );
    }
}
