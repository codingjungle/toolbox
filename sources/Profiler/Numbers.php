<?php

/**
* @brief      Numbers Singleton
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.1
* @version    -storm_version-
*/

namespace IPS\toolbox\Profiler;

use Exception;
use InvalidArgumentException;
use IPS\Patterns\Singleton;
use IPS\toolbox\Profiler\Numbers;

use function bindec;
use function decbin;
use function dechex;
use function decoct;
use function hexdec;
use function implode;
use function number_format;
use function str_split;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Numbers Class
* @mixin Numbers
*/
class _Numbers extends Singleton
{
    /**
    * @brief Singleton Instance
    * @note This needs to be declared in any child class
    * @var static
    */
    public static $instance;


    public function decimal($number){
        if(!(int)$number){
            throw new InvalidArgumentException('Not valid decimal number!');
        }
        return [
            'decimal' => $number,
            'hexa' => dechex($number),
            'octal' => decoct($number),
            'binary' => decbin($number)
        ];
    }

    public function hexa($hex){
        $number = hexdec($hex);
        if(!ctype_xdigit($hex)){
            throw new InvalidArgumentException('Not valid hexadecimal number!');
        }
        return [
            'decimal' => $number,
            'hexa' => $hex,
            'octal' => decoct($number),
            'binary' => decbin($number)
        ];
    }

    public function octal($octal){
        $number = octdec($octal);
        if(!$number){
            throw new InvalidArgumentException('Not valid octal number!');
        }
        return [
            'decimal' => $number,
            'hexa' => dechex($number),
            'octal' => $octal,
            'binary' => decbin($number)
        ];
    }

    public function binary($bin){
        preg_match('#^\b[01]+\b$#',$bin,$matches);
        if(empty($matches)){
            throw new InvalidArgumentException('Not valid binary number!');
        }
        $number = bindec($bin);

        return [
            'decimal' => $number,
            'hexa' => dechex($number),
            'octal' => decoct($number),
            'binary' => $bin
        ];
    }

}