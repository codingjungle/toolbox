<?php

/**
 * @brief       Profiler Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  dtdevplus
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\toolbox\DevCenter\Sources\Generator;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

use IPS\toolbox\Text;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use function defined;
use function header;
use function mb_strtolower;

class _Profiler extends GeneratorAbstract
{
    /**
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     */
    public function bodyGenerator()
    {
        $this->brief = 'Class';
        $profiler = mb_ucfirst( mb_strtolower( $this->type ) );
        $profilerClass = '\\IPS\\dtprofiler\\Profiler\\' . $profiler . '::class';

        if ( $profiler === 'Debug' ) {
            $body = <<<EOF
if( defined('DTPROFILER') && DTPROFILER && class_exists( $profilerClass) ){
    \$class =  $profilerClass;
    if( method_exists(\$class, \$method ) ){
        \$class::{\$method}(...\$args);
    }
}
else if( \$method === 'add' ){
    list( \$message, \$key, ) = \$args;
    \IPS\Log::debug(\$message, \$key);
}
EOF;
        }
        else {
            $body = <<<EOF
if( defined('DTPROFILER') && DTPROFILER && class_exists( $profilerClass) ){
    \$class =  $profilerClass;
    if( method_exists(\$class, \$method ) ){
        \$class::{\$method}(...\$args);
    }
}
EOF;
        }
        $this->methods[] = MethodGenerator::fromArray( [
            'name'       => '__callStatic',
            'parameters' => [
                new ParameterGenerator( 'method' ),
                new ParameterGenerator( 'args' ),
            ],
            'body'       => $body,
            'static'     => \true,
        ] );
    }
}
