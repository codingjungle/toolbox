<?php

/**
 * @brief       SocketUrl Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant;

use IPS\toolbox\Elephant\Exception\MalformedUrlException;

/**
 * Represents a socket URL.
 *
 * @author Toha <tohenk@yahoo.com>
 */
class _SocketUrl
{
    /**
     * @var string
     */
    protected $url = null;

    /**
     * @var string[]
     */
    protected $parsed = null;

    public function __construct($url)
    {
        $this->url = $url;
        $this->parsed = $this->parse($url);
    }

    /**
     * Parse an url into parts we may expect
     *
     * @param string $url
     *
     * @return string[] information on the given URL
     */
    protected function parse($url)
    {
        if (false === $parsed = \parse_url($url)) {
            throw new MalformedUrlException($url);
        }

        $result = \array_replace([
            'scheme' => 'http',
            'host' => 'localhost',
            'query' => []
        ], $parsed);
        if (!isset($result['port'])) {
            $result['port'] = 'https' === $result['scheme'] ? 443 : 80;
        }
        if (!isset($result['path']) || $result['path'] == '/') {
            $result['path'] = 'socket.io';
        }
        if (!\is_array($result['query'])) {
            $query = null;
            \parse_str($result['query'], $query);
            $result['query'] = $query;
        }
        $result['secured'] = 'https' === $result['scheme'];

        return $result;
    }

    /**
     * Get raw URL.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get parsed URL.
     *
     * @return mixed[]
     */
    public function getParsed()
    {
        return $this->parsed;
    }

    /**
     * Get host and port from parsed URL.
     *
     * @return string
     */
    public function getHost()
    {
        return \sprintf('%s:%d', $this->parsed['host'], $this->parsed['port']);
    }

    /**
     * Get address from parsed URL.
     *
     * @return string
     */
    public function getAddress()
    {
        return \sprintf('%s://%s', $this->parsed['secured'] ? 'ssl' : 'tcp', $this->getHost());
    }
}
