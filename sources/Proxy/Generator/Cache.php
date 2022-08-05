<?php
/**
 * @brief      Cache Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox
 * @since      -storm_since_version-
 * @version    -storm_version-
 */


namespace IPS\toolbox\Proxy\Generator;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

use IPS\Patterns\Singleton;

use function defined;
use function file_get_contents;
use function file_put_contents;
use function header;
use function is_file;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * Cache Class
 *
 * @mixin Cache
 */
class _Cache extends Singleton
{

    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static $instance = null;
    protected $path;
    protected $interfaces;
    protected $namespaces;
    protected $classes;
    protected $traits;

    public function __construct()
    {
        parent::__construct();
        $this->path = \IPS\Application::getRootPath().'/dtProxy/';
    }

    public function addClass($class)
    {
        $cs = $this->getClasses();
        $cs[$class] = $class;
        $this->classes[$class] = $class;
        $this->setClasses($cs);
    }

    public function getClasses()
    {
        if($this->classes === null) {
            $return = [];
            $classPath = $this->path . 'classes.json';
            if (is_file($classPath)) {
                $return = json_decode(file_get_contents($classPath), true);
            }
            $this->classes = $return;
        }
        return $this->classes;
    }

    public function setClasses($data)
    {
        $classPath = $this->path . 'classes.json';
        file_put_contents($classPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function addNamespace($namespace)
    {
        $ns = $this->getNamespaces();
        $ns[$namespace] = $namespace;
        $this->namespaces[$namespace] = $namespace;

        $this->setNamespaces($ns);
    }

    public function getNamespaces()
    {
        if($this->namespaces === null) {
            $return = [];
            $namespace = $this->path . 'namespace.json';
            if (is_file($namespace)) {
                $return = json_decode(file_get_contents($namespace), true);
            }
            $this->namespaces = $return;
        }
        return $this->namespaces;
    }

    public function setNamespaces($data)
    {
        $namespace = $this->path . 'namespace.json';
        file_put_contents($namespace, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function addInterfaces($interfaces)
    {
        $ns = $this->getInterfaces();
        $ns[$interfaces] = $interfaces;
        $this->interfaces[$interfaces] = $interfaces;

        $this->setInterfaces($ns);
    }

    public function getInterfaces()
    {
        if($this->interfaces === null) {
            $return = [];
            $interfaces = $this->path . 'interfaces.json';
            if (is_file($interfaces)) {
                $return = json_decode(file_get_contents($interfaces), true);
            }
            $this->interfaces = $return;
        }
        return $this->interfaces;
    }

    public function setInterfaces($data)
    {
        $interfaces = $this->path . 'interfaces.json';
        file_put_contents($interfaces, json_encode($data, JSON_PRETTY_PRINT));
    }


    public function addTraits($traits)
    {
        $ns = $this->getTraits();
        $ns[$traits] = $traits;
        $this->traits[$traits] = $traits;

        $this->setTraits($ns);
    }

    public function getTraits()
    {
        if($this->traits === null) {
            $return = [];
            $traits = $this->path . 'traits.json';
            if (is_file($traits)) {
                $return = json_decode(file_get_contents($traits), true);
            }
            $this->traits = $return;
        }
        return $this->traits;
    }

    public function setTraits($data)
    {
        $traits = $this->path . 'traits.json';
        file_put_contents($traits, json_encode($data, JSON_PRETTY_PRINT));
    }
}

