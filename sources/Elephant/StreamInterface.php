<?php

/**
 * @brief       StreamInterface Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant;

interface StreamInterface
{
    /**
     * Try to connect to stream URL.
     */
    public function connect();

    /**
     * Check if stream is currently connected.
     *
     * @return bool
     */
    public function connected();

    /**
     * Read data from underlying stream.
     *
     * @param int $size
     * @return string
     */
    public function read($size);

    /**
     * Write data to underlying stream.
     *
     * @param string $data
     * @return int
     */
    public function write($data);

    /**
     * Perform HTTP request.
     *
     * @param string $uri
     * @param array $headers
     * @param array $options
     * @return bool
     */
    public function request($uri, $headers = [], $options = []);

    /**
     * Close the stream.
     */
    public function close();

    /**
     * Get url.
     *
     * @return SocketUrl
     */
    public function getUrl();

    /**
     * Get errors from the last connect attempts.
     *
     * @return array
     */
    public function getErrors();

    /**
     * Get stream meta data.
     *
     * @return array
     */
    public function getMetadata();

    /**
     * Get HTTP reponse headers.
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Get HTTP response status.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Get HTTP response status code.
     *
     * @return string
     */
    public function getStatusCode();

    /**
     * Get HTTP response body.
     *
     * @return string
     */
    public function getBody();
}
