<?php

/**
 * @brief       Build Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.8
 * @version     -storm_version-
 */


namespace IPS\toolbox\modules\front\bt;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Dispatcher\Admin;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\toolbox\Application;
use IPS\toolbox\Build\Versions;
use IPS\toolbox\Form;
use IPS\toolbox\Shared\Analyzer;

use RuntimeException;

use function array_merge;
use function explode;
use function preg_match;

use const DT_MY_APPS;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * build
 */
class _build extends \IPS\Dispatcher\Controller
{
    use Analyzer;
    use \IPS\toolbox\Shared\Build;
    /**
     * @inheritdoc
     * @throws RuntimeException
     */
    public function execute()
    {
        Output::i()->cssFiles = array_merge(Output::i()->cssFiles, Theme::i()->css('dtcode.css', 'toolbox', 'admin'));
        parent::execute();
    }
}