<?php

/**
 * @brief       Version3X Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Engine\SocketIO;

/**
 * Implements the dialog with Socket.IO version 3.x
 *
 * @author Toha <tohenk@yahoo.com>
 */
class _Version3X extends Version1X
{

    /** {@inheritDoc} */
    public function getName()
    {
        return 'SocketIO Version 3.X';
    }

    /** {@inheritDoc} */
    protected function getDefaultOptions()
    {
        return \array_merge(parent::getDefaultOptions(), [
            'version' => 4,
        ]);
    }
}
