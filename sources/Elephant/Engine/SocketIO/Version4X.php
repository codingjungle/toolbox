<?php

/**
 * @brief       Version4X Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Engine\SocketIO;

/**
 * Implements the dialog with Socket.IO version 4.x
 *
 * @author Toha <tohenk@yahoo.com>
 */
class _Version4X extends Version1X
{

    /** {@inheritDoc} */
    public function getName()
    {
        return 'SocketIO Version 4.X';
    }

    /** {@inheritDoc} */
    protected function getDefaultOptions()
    {
        return \array_merge(parent::getDefaultOptions(), [
            'version' => 4,
            'max_payload' => 1e6,
        ]);
    }
}
