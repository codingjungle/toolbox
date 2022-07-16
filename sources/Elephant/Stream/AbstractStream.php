<?php

/**
 * @brief       AbstractStream Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Babble
 * @since       3.2.0
 * @version     -storm_version-
 */


namespace IPS\toolbox\Elephant\Stream;

use IPS\toolbox\Elephant\Logger\LoggerInterface;
use IPS\toolbox\Elephant\Logger\NullLogger;
use IPS\toolbox\Elephant\SocketUrl;
use IPS\toolbox\Elephant\StreamInterface;

/**
 * Abstract stream provides abstraction for socket client stream.
 *
 * @author Toha <tohenk@yahoo.com>
 */
abstract class _AbstractStream implements StreamInterface
{
    /**
     * @var SocketUrl
     */
    protected $url = null;

    /**
     * @var array
     */
    protected $context = null;

    /**
     * @var array
     */
    protected $options = null;

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * Constructor.
     *
     * @param string $url
     * @param array $context
     * @param array $options
     */
    public function __construct($url, $context = [], $options = [])
    {
        $this->context = $context;
        $this->options = $options;
        $this->logger = isset($options['logger']) && $options['logger'] ? $options['logger'] : new NullLogger();
        $this->url = new SocketUrl($url);
        $this->initialize();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
    }

    /**
     * Create socket stream.
     *
     * @return StreamInterface
     */
    public static function create($url, $context = [], $options = [])
    {
        $class = SocketStream::class;
        if (isset($options['stream_factory'])) {
            $class = $options['stream_factory'];
            unset($options['stream_factory']);
        }
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('Socket stream class %s not found!', $class));
        }
        $clazz = new $class($url, $context, $options);
        if (!$clazz instanceof StreamInterface) {
            throw new \InvalidArgumentException(\sprintf('Class %s must implmenet StreamInterface!', $class));
        }
        return $clazz;
    }
}
