<?php

/**
 * @brief       DTFileGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Base
 * @since       1.3.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Generator;

use Exception;
use IPS\toolbox\Application;
use IPS\toolbox\Proxy\Proxyclass;
use Symfony\Component\Filesystem\Filesystem;
use Laminas\Code\Generator\Exception\RuntimeException;
use Laminas\Code\Generator\FileGenerator;

use function defined;
use function header;

use function pathinfo;

use const IPS\IPS_FOLDER_PERMISSION;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

Application::loadAutoLoader();

class _DTFileGenerator extends FileGenerator
{

    public $isProxy = false;

    /**
     * @return FileGenerator
     * @throws RuntimeException
     */
    public function write($isPhp = true): FileGenerator
    {
        if ($this->filename !== '') {
            $path = pathinfo($this->filename);
            try {
                $dir = $path['dirname'];
                $fs = new Filesystem();

                if (!$fs->exists($dir)) {
                    $fs->mkdir($dir, IPS_FOLDER_PERMISSION);
                    $fs->chmod($dir, IPS_FOLDER_PERMISSION);
                }
            } catch (Exception $e) {
            }
        }

        $parent = parent::write();

        if ($this->isProxy === false) {
            Proxyclass::i()->buildAndMake($this->filename);
        }

        return $parent;
    }
}
