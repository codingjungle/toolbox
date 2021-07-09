<?php
/**
 * @brief        Background Task
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage    Dev Toolbox
 * @since        26 Feb 2021
 */

namespace IPS\toolbox\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Task\Queue\OutOfRangeException;
use IPS\toolbox\Proxy\Generator\Proxy;
use IPS\toolbox\Proxy\Proxyclass;

use function defined;

use function header;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Background Task
 */
class _Proxy
{
    /**
     * Parse data before queuing
     *
     * @param array $data
     * @return    array
     */
    public function preQueueData($data)
    {
        return $data;
    }

    /**
     * Run Background Task
     *
     * @param mixed $data Data as it was passed to \IPS\Task::queue()
     * @param int $offset Offset
     * @return    int                            New offset
     * @throws    OutOfRangeException    Indicates offset doesn't exist and thus task is complete
     */
    public function run($data, $offset)
    {
        Proxyclass::i()->dirIterator();
        Proxyclass::i()->buildHooks();
        $iterator = Store::i()->dtproxy_proxy_files;
        foreach ($iterator as $key => $file) {
            Proxyclass::i()->build($file);
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
        throw new OutOfRangeException();
    }

    /**
     * Get Progress
     *
     * @param mixed $data Data as it was passed to \IPS\Task::queue()
     * @param int $offset Offset
     * @return    array( 'text' => 'Doing something...', 'complete' => 50 )    Text explaining task and percentage complete
     * @throws    \OutOfRangeException    Indicates offset doesn't exist and thus task is complete
     */
    public function getProgress($data, $offset)
    {
        throw new OutOfRangeException();
    }

    /**
     * Perform post-completion processing
     *
     * @param array $data Data returned from preQueueData
     * @param bool $processed Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
     * @return    void
     */
    public function postComplete($data, $processed = true)
    {
    }
}
