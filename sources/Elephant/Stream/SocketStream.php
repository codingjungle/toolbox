<?php

/**
 * @brief       SocketStream Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Stream;

use function array_merge;
use function count;
use function explode;
use function fclose;
use function fgets;
use function fread;
use function fwrite;
use function implode;
use function is_array;
use function is_resource;
use function sprintf;
use function stream_context_create;
use function stream_get_meta_data;
use function stream_set_timeout;
use function stream_socket_shutdown;
use function stripos;
use function strlen;
use function strtoupper;
use function substr;
use function trim;
use function usleep;
use function utf8_encode;

/**
 * Basic stream to connect to the socket server which behave as an HTTP client.
 *
 * @author Toha <tohenk@yahoo.com>
 */
class _SocketStream extends AbstractStream
{
    public const EOL = "\r\n";

    /**
     * @var resource
     */
    protected $handle = null;

    /**
     * @var array
     */
    protected $errors = null;

    /**
     * @var array
     */
    protected $result = null;

    /**
     * @var array
     */
    protected $metadata = null;

    /**
     * {@inheritDoc}
     */
    protected function initialize()
    {
        $autoConnect = isset($this->options['autoconnect']) ? $this->options['autoconnect'] : true;
        if ($autoConnect) {
            $this->connect();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $errors = [null, null];
        $timeout = isset($this->options['timeout']) ? $this->options['timeout'] : 5; // seconds
        $address = $this->url->getAddress();

        $this->logger->debug(sprintf('Socket connect %s', $address));
        $this->handle = @stream_socket_client(
            $address,
            $errors[0],
            $errors[1],
            $timeout,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
            stream_context_create($this->context)
        );

        if (is_resource($this->handle)) {
            stream_set_timeout($this->handle, $timeout);
        } else {
            $this->errors = $errors;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function connected()
    {
        if (is_resource($this->handle)) {
            $this->metadata = stream_get_meta_data($this->handle);
            return $this->metadata['eof'] ? false : true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function read($size)
    {
        if (is_resource($this->handle)) {
            return fread($this->handle, $size);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write($data)
    {
        if (is_resource($this->handle)) {
            return fwrite($this->handle, (string)$data);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function request($uri, $headers = [], $options = [])
    {
        if (!is_resource($this->handle)) {
            return;
        }

        $method = isset($options['method']) ? $options['method'] : 'GET';
        $skip_body = isset($options['skip_body']) ? $options['skip_body'] : false;
        $payload = isset($options['payload']) ? $options['payload'] : null;

        if ($payload) {
            $contentType = null;
            foreach ($headers as $header) {
                if (substr($header, 0, 13) === 'Content-type:') {
                    $contentType = $header;
                    break;
                }
            }
            if (null === $contentType) {
                $payload = utf8_encode($payload);
                $headers[] = 'Content-type: text/plain;charset=UTF-8';
                $headers[] = 'Content-Length: ' . strlen($payload);
            }
        }

        if (isset($this->options['headers'])) {
            $headers = array_merge($headers, $this->options['headers']);
        }

        $request = array_merge([
            sprintf('%s %s HTTP/1.1', strtoupper($method), $uri),
            sprintf('Host: %s', $this->url->getHost()),
        ], $headers);
        $request = implode(static::EOL, $request) . static::EOL . static::EOL . $payload;

        $this->write($request);

        $this->result = ['headers' => [], 'body' => null];

        // wait for response
        $header = true;
        $len = null;
        $this->logger->debug('Waiting for response!!!');
        while (true) {
            if (!$this->connected()) {
                break;
            }
            if (false === ($content = $header ? fgets($this->handle) : fread($this->handle, $len))) {
                break;
            }
            $this->logger->debug(sprintf('Receive: %s', trim($content)));
            if ($content === static::EOL && $header) {
                if ($skip_body) {
                    break;
                }
                $header = false;
            } elseif ($header) {
                $this->result['headers'][] = trim($content);
                if (null === $len && 0 === stripos($content, 'Content-Length:')) {
                    $len = (int)trim(substr($content, 16));
                }
            } else {
                $this->result['body'] .= $content;
                if ($len === strlen($this->result['body'])) {
                    break;
                }
            }
            usleep($this->options['wait']);
        }

        return count($this->result['headers']) ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        if (!is_resource($this->handle)) {
            return;
        }
        @stream_socket_shutdown($this->handle, STREAM_SHUT_RDWR);
        fclose($this->handle);
        $this->handle = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders()
    {
        return is_array($this->result) ? $this->result['headers'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody()
    {
        return is_array($this->result) ? $this->result['body'] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {
        if (count($headers = $this->getHeaders())) {
            return $headers[0];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode()
    {
        if ($status = $this->getStatus()) {
            list(, $code,) = explode(' ', $status, 3);
            return $code;
        }
    }
}
