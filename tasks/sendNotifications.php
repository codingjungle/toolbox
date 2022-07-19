<?php
/**
 * @brief		sendNotifications Task
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	toolbox
 * @since		15 Jul 2022
 */

namespace IPS\toolbox\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Build;
use IPS\Session\Admin;
use IPS\Settings;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * sendNotifications Task
 */
class _sendNotifications extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
        if(\IPS\DEV_DISABLE_ACP_SESSION_TIMEOUT) {
            $_SERVER[ 'HTTP_HOST' ] = Settings::i()->base_url;
            $_SERVER[ 'QUERY_STRING' ] = '';
            $_SERVER[ 'REQUEST_URI' ] = '';
            $_SERVER[ 'REQUEST_METHOD' ] = 'POST';
            Build::i();
            Admin::i();
            \IPS\core\extensions\core\AdminNotifications\ConfigurationError::runChecksAndSendNotifications();
        }
        return NULL;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}