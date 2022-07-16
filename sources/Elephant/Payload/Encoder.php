<?php

/**
 * @brief       Encoder Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Payload;

use IPS\toolbox\Elephant\AbstractPayload;

/**
 * Encode the payload before sending it to a frame
 *
 * Based on the work of the following :
 *   - Ludovic Barreca (@ludovicbarreca), project founder
 *   - Byeoung Wook (@kbu1564) in #49
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class _Encoder extends AbstractPayload
{
    protected $data;
    /** @var string */
    protected $payload;
    /** @var string[] */
    protected $fragments = [];

    /**
     * @param string $data data to encode
     * @param integer $opCode OpCode to use (one of AbstractPayload's constant)
     * @param bool $mask Should we use a mask ?
     */
    public function __construct($data, $opCode, $mask)
    {
        $this->data = $data;
        $this->opCode = $opCode;
        $this->mask = (bool)$mask;

        if (true === $this->mask) {
            $this->maskKey = \openssl_random_pseudo_bytes(4);
        }
    }

    /**
     * Get payload fragments.
     *
     * @return string[]
     */
    public function getFragments()
    {
        return $this->fragments;
    }

    /**
     * Encode a data payload.
     *
     * @param string $data
     * @param int $opCode
     * @return string
     */
    protected function doEncode($data, $opCode)
    {
        $pack = '';
        $length = \strlen($data);

        if (0xFFFF < $length) {
            $pack = \pack('NN', ($length & 0xFFFFFFFF00000000) >> 0b100000, $length & 0x00000000FFFFFFFF);
            $length = 0x007F;
        } elseif (0x007D < $length) {
            $pack = \pack('n*', $length);
            $length = 0x007E;
        }

        $payload = ($this->fin << 0b001) | $this->rsv[0];
        $payload = ($payload << 0b001) | $this->rsv[1];
        $payload = ($payload << 0b001) | $this->rsv[2];
        $payload = ($payload << 0b100) | $opCode;
        $payload = ($payload << 0b001) | $this->mask;
        $payload = ($payload << 0b111) | $length;

        $payload = \pack('n', $payload) . $pack;

        if (true === $this->mask) {
            $payload .= $this->maskKey;
            $data = $this->maskData($data);
        }

        return $payload . $data;
    }

    /**
     * Encode data.
     *
     * @return \IPS\toolbox\Elephant\Payload\Encoder
     */
    public function encode()
    {
        if (null === $this->payload) {
            $data = $this->data;
            $length = \strlen($data);
            $size = \min($this->maxPayload > 0 ? $this->maxPayload : $length, $length);

            $this->fin = 0b0;
            $opCode = $this->opCode;
            while (\strlen($data) > 0) {
                $count = $size;
                // reduce count with framing protocol size
                if ($count === $this->maxPayload) {
                    if ($count > 125) {
                        $count -= (0xFFFF >= $count) ? 2 : 8;
                    }
                    if (true === $this->mask) {
                        $count -= \strlen($this->maskKey);
                    }
                    $count -= 2;
                }

                // create payload fragment
                $s = \substr($data, 0, $count);
                $data = \substr($data, $count);
                if (0 === \strlen($data)) {
                    $this->fin = 0b1;
                }

                $this->fragments[] = $this->doEncode($s, $opCode);
                $opCode = static::OPCODE_CONTINUE;
            }

            $this->payload = \implode('', $this->fragments);
        }

        return $this;
    }

    public function __toString()
    {
        $this->encode();

        return $this->payload;
    }
}
