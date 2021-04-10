<?php

/**
 * @brief       Template Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Code Analyzer
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\toolbox\Code;


use Exception;
use IPS\toolbox\extensions\core\FileStorage\FileStorage;
use IPS\toolbox\Profiler\Debug;
use UnderflowException;

use function defined;
use function get_class;
use function header;
use function is_bool;
use function is_numeric;
use function json_decode;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _FileStorage extends ParserAbstract
{

    protected $warnings;

    protected $finder;

    /**
     * @inheritdoc
     */
    public function check(): array
    {
        $warnings = [];
        $extensions = $this->app->extensions('core', 'FileStorage');
        $settings = json_decode(\IPS\Settings::i()->upload_settings, true);
        $id = 1;
        if (isset($settings['filestorage__toolbox_FileStorage'])) {
            $id = $settings['filestorage__toolbox_FileStorage'];
        }
        Debug::log($id);
        if (empty($extensions) === false) {
            /** @var FileStorage $extension */
            foreach ($extensions as $extension) {
                $class = get_class($extension);
                try {
                    //this should be a int
                    $count = $extension->count();
                    if (!is_numeric($count)) {
                        throw new Exception('The count method returned something other than a integer!');
                    }

                    //if this doesn't return a int or throw any other error than UnderflowException, then something is wrong with the extension!
                    try {
                        $move = $extension->move(0, $id, null);
                        if (!is_numeric($move) && empty($move) === false) {
                            throw new \InvalidArgumentException(
                                'The move method returned something other than a integer!'
                            );
                        }
                    } catch (UnderflowException $e) {
                    } catch (\InvalidArgumentException $e) {
                        throw new \Exception($e->getMessage() . "\n" . $e->getTraceAsString());
                    } catch (\Exception $e) {
                        throw new \Exception(
                            "The move method threw an exception other than an UnderFlowException.\nException Thrown: " . \get_class(
                                $e
                            ) . "\n" . $e->getMessage() . $e->getTraceAsString()
                        );
                    }


                    try {
                        $valid = $extension->isValidFile('foobar.jpg');
                        if (!is_bool($valid)) {
                            throw new \InvalidArgumentException(
                                'The isValidFile method returned something other than a bool!'
                            );
                        }
                    } catch (\InvalidArgumentException $e) {
                        throw new \Exception($e->getMessage() . "\n" . $e->getTraceAsString());
                    } catch (\Exception $e) {
                        throw new Exception(
                            "The isValidFile method threw an exception! This method shouldn't throw an exception!\nException Thrown: " . \get_class(
                                $e
                            ) . "\n" . $e->getMessage() . $e->getTraceAsString()
                        );
                    }

                    try {
                        $deleted = $extension->delete();
                        if (empty($deleted) === false) {
                            throw new \InvalidArgumentException(
                                'The delete method returned a value, it shouldn\'t be void.'
                            );
                        }
                    } catch (\InvalidArgumentException $e) {
                        throw new \Exception($e->getMessage() . "\n" . $e->getTraceAsString());
                    } catch (\Exception $e) {
                        throw new Exception(
                            "The delete method threw an exception! This method shouldn't throw an exception!\nException Thrown: " . \get_class(
                                $e
                            ) . "\n" . $e->getMessage() . $e->getTraceAsString()
                        );
                    }
                } catch (Exception $e) {
                    $name = $class;
                    $class = $this->app->getApplicationPath() . '/' . str_replace(
                            ["\\", "IPS", '/' . $this->app->directory . '/'],
                            ['/', '', ''],
                            $class
                        ) . '.php';
                    //if they throw any error that isn't expected from the methods, then we consider it faulty.
                    $warnings[] = [
                        'path' => ['url' => $this->buildPath($class, 0), 'name' => $name],
                        'pre' => $e->getMessage()
                    ];
                }
            }
        }
        return $warnings;
    }

}
