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
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\ValueGenerator;

use function defined;
use function header;

if ( ! defined('\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Log implements HelpersAbstract
{
	/**
	 * @inheritdoc
	 */
	public function process($class, &$classDoc, &$classExtends, &$body)
	{
		try
		{
			$methodDocBlock = new DocBlockGenerator(
				'Write a log message', null,
				[
					new ParamTag( 'message', '\Exception|string|array', 'An Exception object, an array or a generic message to log' ),
					new ParamTag( 'category', 'string|null', 'An optional string identifying the type of log (for example "upgrade")' ),
				]
			);

			$body[] = MethodGenerator::fromArray(
				[
					'name'       => 'log',
					'parameters' => [
						new ParameterGenerator('message', null, null, 0),
						new ParameterGenerator('category', NULL, new ValueGenerator( NULL, ValueGenerator::TYPE_NULL ), 1),

					],
					'body'       => 'return parent::log( ...\func_get_args() );',
					'docblock'   => $methodDocBlock,
					'static'     => true,
				]
			);
		} catch ( InvalidArgumentException ) {}
	}
}
