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
use Laminas\Code\Generator\ValueGenerator;

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
                        new ParameterGenerator('idField', null, new ValueGenerator(null, ValueGenerator::TYPE_NULL), 1),
                        new ParameterGenerator(
                            'extraWhereClause',
                            null,
                            new ValueGenerator(null, ValueGenerator::TYPE_NULL),
                            2
                        ),
                    ],
                    'body'       => 'return parent::load($id,$idField,$extraWhereClause);',
                    'docblock'   => $methodDocBlock,
                    'static'     => true,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }

        try {
            $methodDocBlock = new DocBlockGenerator(
                'Construct ActiveRecord from database row', null, [
                    new ParamTag('data', 'array'),
                    new ParamTag('updateMultitonStoreIfExists', 'bool'),
                    new ReturnTag('static')

                ]
            );
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'constructFromData',
                    'parameters' => [
                        new ParameterGenerator('data', null, null, 0),
                        new ParameterGenerator(
                            'updateMultitonStoreIfExists', null, new ValueGenerator(
                            false,
                            ValueGenerator::TYPE_BOOL
                        ), 1
                        )
                    ],
                    'body'       => 'return parent::constructFromData($data,$updateMultitonStoreIfExists);',
                    'docblock'   => $methodDocBlock,
                    'static'     => true,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }
    }
}
