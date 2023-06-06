<?php

/**
* @brief      CodeAnalyzerAbstract Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Code\Abstracts;


use IPS\Application;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* CodeAnalyzerAbstract Class
* @mixin \IPS\toolbox\Code\Abstracts\CodeAnalyzerAbstract
*/
abstract class _CodeAnalyzerAbstract
{
    /**
     * paths to look in, by default it looks in the applications root folder
     * @param string $class the current parser
     * @param Application $app the app object
     * @return null
     */
    public function getPaths(string $class, Application $app){
        return null;
    }

    /**
     * which file extensions to filter by
     * @param string $class
     * @return null
     */
    public function getExtensions(string $class){
        return null;
    }

    /**
     * @param ParserAbstract $class the current parser using the plugin, so you can target different folders
     * folders to exclude on the collection of files to process in the code analyzer. return null to use defaults
     * @return array|null
     */
    public function excludedFolders(string $class): ?array
    {
        return null;
    }

    /**
     * @param ParserAbstract $class the current parser using the plugin, so you can target different files
     * files to exclude on collection to process in the code analyzer. return null to use defaults
     * @return array|null
     */
    public function excludedFiles(string $class): ?array
    {
        return null;
    }
}