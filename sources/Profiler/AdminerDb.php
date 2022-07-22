<?php

/**
* @brief      AdminerDb Singleton
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.5
* @version    -storm_version-
*/

namespace IPS\toolbox\Profiler;

use IPS\Patterns\Singleton;
use IPS\toolbox\AdminerDb;
use IPS\toolbox\Application;
use Stringable;

use function _p;
use function array_diff_assoc;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function preg_match;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* AdminerDb Class
* @mixin AdminerDb
*/
class _AdminerDb extends Singleton
{
    /**
    * @brief Singleton Instance
    * @note This needs to be declared in any child class
    * @var static
    */
    public static $instance;

    public function run(string|Stringable $table){
        preg_match('#^(.*?)_(.*?)$#',$table,$matches);
        if(!isset($matches[1])){
            return;
        }
        $app = $matches[1];
        $application = Application::load($app);
        /* Get table definition */
        $schema = $this->getSchema( $application );
        $dbDefinition = [];
        $schemaJson = \IPS\Db::i()->normalizeDefinition( $schema[ $table] );
        unset( $schemaJson['inserts'] );
        unset( $schemaJson['comment'] );
        unset( $schemaJson['reporting'] );
        try
        {
            $dbDefinition = $this->_getTableDefinition( $schemaJson['name'] );
            $dbDefinition = \IPS\Db::i()->normalizeDefinition( $dbDefinition );
            unset( $dbDefinition['comment'] );
        }
        catch ( \OutOfRangeException $e )
        {
        }

        $changes = $this->compare($dbDefinition,$schemaJson);
        _p($changes);

    }

    protected function compare($db,$schema){
        $changes = [];
        $changesIndex = [];
        $dbColumns = $db['columns'] ?? [];
        $schemaColumns = $schema['columns'] ?? [];
        $foo = $dbColumns;
        $foo2 = $schemaColumns;
        if(empty($dbColumns) && empty($schemaColumns) === false){
            $changes['removed'] = $schemaColumns;
        }

        if(empty($dbColumns) === false && empty($schemaColumns)){
            $changes['added'] = $dbColumns;
        }

        if(empty($schemaColumns) === false){
            foreach($schemaColumns as $key => $data ){
                if( isset($dbColumns[$key]) && empty(array_diff_assoc($dbColumns[$key],$data) )=== false ){
                    $changes['changed'][$key] = $dbColumns[$key];
                    unset($dbColumns[$key]);
                    unset($schemaColumns[$key]);
                }
                elseif( isset($dbColumns[$key]) && empty(array_diff_assoc($dbColumns[$key],$data) ) ) {
                    unset($dbColumns[$key]);
                    unset($schemaColumns[$key]);
                }
                elseif(!isset($dbColumns[$key])){
                    $changes['removed'][$key] = $data;
                    unset($schemaColumns[$key]);
                }
            }
            $changes['added'] = $dbColumns;
        }

        $dbIndexes = $dbColumns['indexes'];
        $schemaIndexes = $schema['indexes'];

        if(empty($dbIndexes) && empty($schemaIndexes) === false){
            $changesIndex['removed'] = $schemaColumns;
        }

        if(empty($dbIndexes) === false && empty($schemaIndexes)){
            $changesIndex['added'] = $dbIndexes;
        }

        if(empty($schemaIndexes) === false){
            foreach($schemaIndexes as $key => $data ){
                if( isset($dbIndexes[$key]) && empty(array_diff_assoc($dbIndexes[$key],$data) )=== false ){
                    $changesIndex['changed'][$key] = $dbIndexes[$key];
                    unset($dbIndexes[$key]);
                    unset($schemaIndexes[$key]);
                }
                elseif( isset($dbIndexes[$key]) && empty(array_diff_assoc($dbIndexes[$key],$data) ) ) {
                    unset($dbIndexes[$key]);
                    unset($schemaIndexes[$key]);
                }
                elseif(!isset($dbIndexes[$key])){
                    $changesIndex['removed'][$key] = $data;
                    unset($schemaIndexes[$key]);
                }
            }
            $changesIndex['added'] = $dbIndexes;
        }

        return [
            'name' => $dbColumns['name'],
            'columns' => $changes,
            'indexes' => $changesIndex
        ];
    }

    protected function getSchema($application){
        $file = \IPS\ROOT_PATH . "/applications/{$application->directory}/data/schema.json";
        return json_decode( file_get_contents( $file ), TRUE );
    }

    protected function _getTableDefinition( $name )
    {
        $definition = \IPS\Db::i()->getTableDefinition( $name );
        foreach ( \IPS\Application::applications() as $app )
        {
            $file = \IPS\ROOT_PATH . "/applications/{$app->directory}/setup/install/queries.json";
            if ( file_exists( $file ) )
            {
                foreach( json_decode( file_get_contents( $file ), TRUE ) as $query )
                {
                    if ( $query['method'] === 'addColumn' and $query['params'][0] === $definition['name'] )
                    {
                        unset( $definition['columns'][ $query['params'][1]['name'] ] );
                    }
                }
            }
        }
        return $definition;
    }
}