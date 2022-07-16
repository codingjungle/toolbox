<?php

/**
 * @brief       EngineInterface Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant;

use IPS\toolbox\Elephant\Logger\LoggerAwareInterface;

/**
 * Represents an engine used within IPS\toolbox\Elephant to send / receive messages from
 * a websocket real time server
 *
 * Loosely based on the work of the following :
 *   - Ludovic Barreca (@ludovicbarreca)
 *   - Mathieu Lallemand (@lalmat)
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
interface EngineInterface extends LoggerAwareInterface
{
    /**
     * Connect to the targeted server
     */
    public function connect();

    /** Closes the connection to the websocket */
    public function close();

    /**
     * Read data from the socket
     *
     * @return string Data read from the socket
     */
    public function read();

    /**
     * Emits a message through the websocket
     *
     * @param string $event Event to emit
     * @param array $args Arguments to send
     */
    public function emit($event, array $args);

    /**
     * Wait for event to arrive.
     *
     * @param string $event
     * @return \stdClass
     */
    public function wait($event);

    /**
     * Keeps alive the connection
     */
    public function keepAlive();

    /**
     * Gets the name of the engine
     *
     * @return string
     */
    public function getName();

    /**
     * Sets the namespace for the next messages
     *
     * @param string $namespace the namespace
     */
    public function of($namespace);
}
