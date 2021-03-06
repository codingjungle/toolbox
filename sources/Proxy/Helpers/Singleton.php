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

use IPS\toolbox\Profiler\Debug;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\Exception\InvalidArgumentException;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER[ 'SERVER_PROTOCOL' ]) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _Singleton implements HelpersAbstract
{
    /**
     * @inheritdoc
     */
    public function process($class, &$classDoc, &$classExtends, &$body)
    {

        try{
            $propertyDocBlock = new DocBlockGenerator(
                'Instance of  class', null, [ new ReturnTag('static')]
            );
            $body[] = PropertyGenerator::fromArray(
                [
                    'name' => 'instance',
                    'static' => true,
                    'docblock' => $propertyDocBlock,
                    'visibility' => 'protected'
                ]
            );
        }
        catch(InvalidArgumentException $e){
        }

        $methodDocBlock = new DocBlockGenerator(
            'Send JSON output', \null, [
                new ReturnTag('static')
            ]
        );

        try {
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'i',
                    'parameters' => [],
                    'body'       => 'return parent::i();',
                    'docblock'   => $methodDocBlock,
                    'static'     => \true,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }
    }
}
