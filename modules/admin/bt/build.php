<?php

/**
 * @brief       Build Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.14
 * @version     -storm_version-
 */


namespace IPS\toolbox\modules\admin\bt;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\toolbox\Shared\Analyzer;

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
     * @brief    Has been CSRF-protected
     */
    public static $csrfProtected = true;
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'build_manage' );
		parent::execute();
	}
}