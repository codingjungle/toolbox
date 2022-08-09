<?php

/**
* @brief      Application Application Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.1
* @version    -storm_version-
*/

namespace IPS\toolbox;

use IPS\Output;
use IPS\Theme;
use IPS\toolbox\ApplicationOG;
use function array_merge;
use function array_combine;
use function strrev;
use function dechex;
use function crc32;
use function mb_substr;
use function mb_strlen;
use function str_pad;
use function random_int;
use function floor;
use const STR_PAD_LEFT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Application Class
* @mixin \IPS\toolbox\Application
*/
class _Application extends ApplicationOG
{

    /**
     * @param array $files an array of js files to load, without .js, eg ['front_myjs','front_myjs2'],
     * will use the app it is called from, but you can load other apps js if need be by adding the app
     * to the value in the array, eg ['core_front_somejs','front_myjs','front_myjs2'], the first
     * value will load from core, the next 2 will load from your app.
     * @return void
     */
    public static function addJs(array $files): void
    {
        $app = 'toolbox';
        $jsFiles[] = Output::i()->jsFiles;
        foreach ($files as $f) {
            $v = explode('_', $f);
            //determine if we need to change the $app
            if(\count($v) === 2){
                [$loc, $file] = explode('_',$f);
            }
            else {
                [$app, $loc, $file] =  explode('_',$f);
            }
            $file = $loc . '_' . $file . '.js';
            //add to local variable for merging
            $jsFiles[] = Output::i()->js($file, $app, $loc);
        }
        //merges $jsFiles into Output::i()->jsFiles
        Output::i()->jsFiles = array_merge(...$jsFiles);
    } 

    /**
     * @param array $files an array of css files to load, without .css, eg ['front_mycss','front_mycss2'],
     * will use the app it is called from, but you can load other apps css if need be by adding the app
     * to the value in the array, eg ['core_front_somecss','front_mycss','front_mycss2'], the first
     * value will load from core, the next 2 will load from your app.
     * @return void
     */
    public static function addCss(array $files): void
    { 
        $app = 'toolbox';
        $cssFiles[] = Output::i()->cssFiles;
        foreach ($files as $f) {
            $v = explode('_', $f);
            //determine if we need to change the $app
            if(\count($v) === 2){
                [$loc, $file] = explode('_',$f);
            }
            else {
                [$app, $loc, $file] =  explode('_',$f);
            }
            $file = $loc . '_' . $file . '.css';
            $cssFiles[] = Theme::i()->css($file, $app, $loc);
        }
        //merges $cssFiles into Output::i()->cssFiles
        Output::i()->cssFiles = array_merge(...$cssFiles);
    }

    /**
     * @param array $jsVars a key/value array of jsVars to add, ['mykey' => 'value']
     * @return void
     */
    public static function addJsVar(array $jsVars): void
    {
        foreach ($jsVars as $key => $jsVar) {
            Output::i()->jsVars[$key] = $jsVar;
        }
    }
}