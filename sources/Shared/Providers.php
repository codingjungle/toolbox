<?php

/**
 * @brief       Providers Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.1
 * @version     -storm_version-
 */


namespace IPS\toolbox\Shared;

use IPS\toolbox\Proxy\Generator\Writer;

interface Providers
{
    public function meta(array &$jsonMeta);

    public function writeProvider(Writer $generator);
}
