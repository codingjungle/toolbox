<?php
/**
 * @brief		proxyTask Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	toolbox
 * @since		25 May 2023
 */

namespace IPS\toolbox\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\toolbox\Profiler\Debug;
use IPS\toolbox\Proxy\Generator\Proxy;
use IPS\toolbox\Proxy\Proxyclass;

use Throwable;

use const IPS\NO_WRITES;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * proxyTask Task
 */
class _proxyTask extends \IPS\Task
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
        if(Settings::i()->toolbox_use_proxy_task && isset(Store::i()->dtproxy_md5)){
            try {
                if (NO_WRITES === true) {
                    throw new \Exception('Proxy generator can not be used atm, NO_WRITES is enabled in the constants.php.');
                }

                Proxyclass::i()->dirIterator(null, false, false);
                Proxyclass::i()->buildHooks();
                $iterator = Store::i()->dtproxy_proxy_files;
                foreach ($iterator as $key => $file) {
                    try {
                        Proxyclass::i()->build($file);
                    }catch( \InvalidArgumentException $e){
                        continue;
                    }
                }
                unset(Store::i()->dtproxy_proxy_files);
                Proxy::i()->buildConstants();
                $step = 1;
                do {
                    $step = Proxyclass::i()->makeToolboxMeta($step);
                } while ($step !== null);
                Proxy::i()->generateSettings();
                Proxyclass::i()->buildCss();
                unset(Store::i()->dtproxy_proxy_files, Store::i()->dtproxy_templates);
            } catch (Throwable $e) {
                Debug::log($e);
            }
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