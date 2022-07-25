<?php

/**
* @brief      MyApps Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.8
* @version    -storm_version-
*/

namespace IPS\toolbox\Profiler;

use IPS\Application;
use IPS\toolbox\Build\Versions;
use IPS\toolbox\Form;
use IPS\toolbox\Profiler\MyApps;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* MyApps Class
* @mixin MyApps
*/
class _MyApps
{

    /**
    * _MyApps constructor
    *
    */
    public function __construct($checkAccess )
    {

        
    }

    public function addForm( Form $form )
    {

    }

    /**
     * Values are saved in a store, so make sure you are formatting your values to be stored in this manner (no objects).
     * @param $values
     * @return void
     */
    public function formatValues(&$values){

    }

    public function beforeBuild(Versions $versions, &$filename, &$savePath)
    {

    }

    public function slasherDirExclude(array &$dirs)
    {

    }

    public function slasherFileExclude(array &$files)
    {

    }
}