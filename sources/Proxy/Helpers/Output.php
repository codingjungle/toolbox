<?php

/**
 * @brief       IPSDataStore Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox\Proxy
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox\Proxy\Helpers;

use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Output implements HelpersAbstract
{
    /**
     * @inheritdoc
     */
    public function process($class, &$classDoc, &$classExtends, &$body)
    {
        try {
            $propertyDocBlock = new DocBlockGenerator(
                'Instance of  class', null, [new ReturnTag('static')]
            );
            $body[] = PropertyGenerator::fromArray(
                [
                    'name'       => 'instance',
                    'static'     => true,
                    'docblock'   => $propertyDocBlock,
                    'visibility' => 'protected'
                ]
            );
        } catch (InvalidArgumentException $e) {
        }
        try {
            $methodDocBlock = new DocBlockGenerator(
                'Instance of  class', null, [
                    new ReturnTag('static')
                ]
            );
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'i',
                    'parameters' => [],
                    'body'       => 'return parent::i();',
                    'docblock'   => $methodDocBlock,
                    'static'     => true,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }

        try {
            $methodDocBlock = new DocBlockGenerator(
                'Send JSON output', null, [
                    new ParamTag('data', 'array|string'),
                    new ParamTag('httpStatusCode', 'int'),
                ]
            );
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'json',
                    'parameters' => [
                        new ParameterGenerator('data', null, null, 0),
                        new ParameterGenerator('httpStatusCode', 'int', 200, 1),

                    ],
                    'body'       => 'return parent::json( ...\func_get_args() );',
                    'docblock'   => $methodDocBlock,
                    'static'     => false,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }
    }
}
