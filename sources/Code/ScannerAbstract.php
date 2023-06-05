<?php

/**
* @brief      ScannerAbstract Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Code;


if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* ScannerAbstract Class
* @mixin \IPS\toolbox\Code\ScannerAbstract
*/
abstract class _ScannerAbstract
{
    /**
     * return array of folder paths not to include in the class scanner
     * @return array
     */
    public function excludedFolders() : array
    {
        return [];
    }

    /**
     * okay this one is a bit weird, in the class scanner we go all the way back to the root class, but sometimes that isn't always desirable, so here we can tell the class scanner where to stop. return an array of Underscored IPS classes here
     * @return array
     */
    public function fullStop() : array
    {
        return [];
    }

    /**
     * return a multi-diminsional array of underscored IPS class as array key, then an array of methods to skip checking if they call parent or not. this is useful when the method is meant to be fully overridden and the parent is not meant to be used, exampled of this is getStore from \IPS\Patterns\_ActiveRecord
     * @return array
     */
    public function autoLint() : array
    {
        return [];
    }
}