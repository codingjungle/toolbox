<?php

/**
 * @brief       Version2X Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Engine\SocketIO;

/**
 * Implements the dialog with Socket.IO version 2.x
 *
 * Based on the work of Mathieu Lallemand (@lalmat)
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 * @link https://tools.ietf.org/html/rfc6455#section-5.2 Websocket's RFC
 */
class _Version2X extends Version1X
{

    /** {@inheritDoc} */
    public function getName()
    {
        return 'SocketIO Version 2.X';
    }

    /** {@inheritDoc} */
    protected function getDefaultOptions()
    {
        return \array_merge(parent::getDefaultOptions(), [
            'version' => 3,
        ]);
    }
}
