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
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class _ActiveRecord implements HelpersAbstract
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
                    'name'       => 'multiton',
                    'static'     => true,
                    'docblock'   => $propertyDocBlock,
                    'visibility' => 'protected'
                ]
            );
        } catch (InvalidArgumentException $e) {
        }

        try {
            $methodDocBlock = new DocBlockGenerator(
                'Load Record', null, [
                    new ParamTag('id', 'int|string'),
                    new ParamTag('idField', 'string',),
                    new ParamTag('extraWhereClause', 'mixed'),
                    new ReturnTag('static')

                ]
            );
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'load',
                    'parameters' => [
                        new ParameterGenerator('id', null, null, 0),
                        new ParameterGenerator('idField', 'string', 'null', 1),
                        new ParameterGenerator('extraWhereClause', null, 'null', 2),
                    ],
                    'body'       => 'return parent::load($id,$idField,$extraWhereClause);',
                    'docblock'   => $methodDocBlock,
                    'static'     => true,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }
    }
}
