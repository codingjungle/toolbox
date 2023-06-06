<?php

/**
* @brief      Todo Active Record
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Code\Utils;

use IPS\Patterns\ActiveRecord;
use IPS\toolbox\Application;
use IPS\toolbox\Editor;
use IPS\toolbox\Traits\Orm;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Todo Class
* @mixin \IPS\toolbox\Code\Todo
 * @
*/
class _Todo extends ActiveRecord
{

    use Orm;

    /**
    * @brief [ActiveRecord] Multion Store
    * @var  array
    */
    protected static $multitons;

    /**
    * @brief	[ActiveRecord] Multiton Map
    * @var  array
    */
    protected static $multitonMap;

    /**
    * @brief [ActiveRecord] Database Prefix
    * @var string
    */
    public static $databasePrefix = 'todo_';

    /**
    * @brief [ActiveRecord] Database table
    * @var string
    */
    public static $databaseTable = 'toolbox_todo';

    /**
    * @brief [ActiveRecord] Bitwise Keys
    * @var array
    */
    public static $bitOptions = array(
        'bitwise' => array(
            'bitwise' => array()
        )
    );

    /**
     * _Todo constructor
     *
     */
    public static function analyze(string $app){
        $finder = new Finder();
        $finder->in(Application::getRootPath($app) . DIRECTORY_SEPARATOR .$app);
        $filter = function (SplFileInfo $file) {
            if ($file->getExtension() != 'php') {
                return false;
            }

            return true;
        };

        $finder->filter($filter);
    }

    public function get_url(){
        return (new Editor())->replace($this->file, $this->line);
    }

    public function get_name(){
        return str_replace(Application::getRootPath('toolbox').DIRECTORY_SEPARATOR.'applications'.DIRECTORY_SEPARATOR,'', $this->file);
    }
}