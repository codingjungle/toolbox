<?php

/**
 * @brief           Sockets Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package         IPS Social Suite
 * @subpackage      Babble
 * @since           1.0.0 Beta 1
 * @version     -storm_version-
 */

namespace IPS\toolbox\Profiler;

use Exception;
use IPS\toolbox\Elephant\Client;
use IPS\toolbox\Profiler\Debug;
use IPS\Http\Url;
use IPS\Patterns\Singleton;

use function defined;
use function header;

use const DT_NODE;
use const DT_NODE_URL;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _Sockets
 *
 * @package IPS\babble
 * @mixin Sockets
 */
class _Sockets extends Singleton
{

    protected static $instance;

    /**
     * @param        $message
     * @param Member $member
     * @param bool $multi
     *
     * @return void
     */
    public function post($message, $multi = false)
    {
        try {
            $client = $this->ioClient();
            $client->initialize();

            if ($multi === false) {
                $client->emit('notify', $message);
            } else {
                foreach ($message as $m) {
                    $client->emit('notify', $m);
                }
            }
            $client->close();
        } catch (Exception $e) {
        }
    }

    /**
     * @brief we use a php based client to handle some of the things we do in babble. Useful since it is nodejs based.
     * @return Client
     */
    public function ioClient(): Client
    {
        $handshake = defined('DT_NODE_URL') && DT_NODE ? DT_NODE_URL : '';
        $url = Url::createFromString($handshake);
        $options = [];

        if ($url->data['scheme'] === 'https') {
            $options['context']['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ];
        }

        $options['site'] = \IPS\SUITE_UNIQUE_KEY;
        $options['timeout'] = 10;

        return new Client(Client::engine(Client::CLIENT_4X, $handshake, $options));
    }
}
