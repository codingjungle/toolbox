<?php

/**
* @brief      Dates Singleton
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.1
* @version    -storm_version-
*/

namespace IPS\toolbox\Profiler;

use IPS\DateTime;
use IPS\Patterns\Singleton;
use IPS\toolbox\Profiler\Dates;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Dates Class
* @mixin Dates
*/
class _Dates extends Singleton
{
    /**
    * @brief Singleton Instance
    * @note This needs to be declared in any child class
    * @var static
    */
    public static $instance;

    public function dates($date = null){

            $time = strtotime($date);
            $date = DateTime::ts($time,false);

        return [
            'date' => $date->format('Y-m-d\TH:i'),
            'unix' => $date->getTimestamp(),
            'iso' => $date->format('c')
        ];
    }

    public function unix($int){
        $date = DateTime::ts($int,false);

        return [
            'date' => $date->format('Y-m-d\TH:i'),
            'unix' => $date->getTimestamp(),
            'iso' => $date->format('c')
        ];
    }

    public function iso($iso){
        return $this->dates($iso);
    }
}