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

use IPS\Lang;
use Zend\Code\Generator\DocBlock\Tag\AbstractTypeableTag;
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

class _Member implements HelpersAbstract
{
    /**
     * @inheritdoc
     */
    public function process($class, &$classDoc, &$classExtends, &$body)
    {
        try {
            $propertyDocBlock = new DocBlockGenerator(
                'Store a reference to the language object', null, [new ReturnTag('\\' . Lang::class)]
            );
            $body[] = PropertyGenerator::fromArray(
                [
                    'name'       => '_lang',
                    'static'     => true,
                    'docblock'   => $propertyDocBlock,
                    'visibility' => 'protected'
                ]
            );
        } catch (InvalidArgumentException $e) {
        }

        $methodDocBlock = new DocBlockGenerator(
            'Return the language object to use for this member - returns default if member has not selected a language',
            null, [
                new ReturnTag('\\' . Lang::class)
            ]
        );

        try {
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'language',
                    'parameters' => [
                        new ParameterGenerator('frontOnly', null, 'false', 0),
                    ],
                    'body'       => 'return parent::language();',
                    'docblock'   => $methodDocBlock,
                    'static'     => false,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }

        $methodDocBlock = new DocBlockGenerator(
            '',
            null, [
                new ReturnTag('\\' . \IPS\Member::class)
            ]
        );

        try {
            $body[] = MethodGenerator::fromArray(
                [
                    'name'       => 'loggedIn',
                    'parameters' => [],
                    'body'       => 'return parent::loggedIn();',
                    'docblock'   => $methodDocBlock,
                    'static'     => true,
                ]
            );
        } catch (InvalidArgumentException $e) {
        }
    }
}
