<?php

/**
 * @brief       Session Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Engine;

use InvalidArgumentException;

/**
 * Represents the data for a Session
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class _Session
{
    /** @var integer session's id */
    protected $id;

    /** @var integer session's last heartbeat */
    protected $heartbeat;

    /** @var float[] session's and heartbeat's timeouts */
    protected $timeouts;

    /** @var string[] supported upgrades */
    protected $upgrades;

    public function __construct($id, $interval, $timeout, array $upgrades)
    {
        $this->id = $id;
        $this->upgrades = $upgrades;
        $this->heartbeat = $this->getTime();

        $this->timeouts = [
            'timeout' => (float)$timeout,
            'interval' => (float)$interval
        ];
    }

    /**
     * The property should not be modified, hence the private accessibility on them
     *
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        static $list = ['id', 'upgrades'];

        if (!\in_array($prop, $list)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Unknown property "%s" for the Session object. Only the following are availables : ["%s"]',
                    $prop,
                    \implode('", "', $list)
                )
            );
        }

        return $this->$prop;
    }

    protected function getTime()
    {
        return \microtime(true);
    }

    /**
     * Get timeout.
     *
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeouts['timeout'];
    }

    /**
     * Get interval.
     *
     * @return float
     */
    public function getInterval()
    {
        return $this->timeouts['interval'];
    }

    /**
     * Checks whether a new heartbeat is necessary, and does a new heartbeat if it is the case
     *
     * @return bool true if there was a heartbeat, false otherwise
     */
    public function needsHeartbeat()
    {
        if (0 < $this->timeouts['interval'] && $this->getTime(
            ) > ($this->timeouts['interval'] + $this->heartbeat - 5)) {
            $this->heartbeat = $this->getTime();

            return true;
        }

        return false;
    }

    public function __toString()
    {
        return \json_encode([
            'id' => $this->id,
            'heartbeat' => $this->heartbeat,
            'timeouts' => $this->timeouts,
            'upgrades' => $this->upgrades,
        ]);
    }
}
