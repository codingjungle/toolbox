<?php

/**
* @brief      Templates Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Code;

use IPS\Theme;
use IPS\toolbox\Code\ParserAbstract;
use Symfony\Component\Finder\Finder;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Templates Class
* @mixin \IPS\toolbox\Code\Templates
*/
class _Templates extends ParserAbstract
{
    protected function getFiles()
    {
        $files = new Finder();
        $files->in($this->getAppPath() . 'dev/html')->name('*.phtml');
        if (empty($this->skip) === false) {
            $files->notName($this->skip);
        }
        $this->files = $files->files();
    }

    public function validate(): array
    {
        $warnings = [
            'errors' => []
        ];
        //return
        foreach($this->files as $file){
            $html = $file->getContents();
            $path = $file->getRelativePath();
            $method = $file->getFilenameWithoutExtension();
            $params = array();
            [$loc,$group] = explode('/', $path);
            $class = $loc.$group.$method;
            /* Parse the header tag */
            preg_match( '/^<ips:template parameters="(.+?)?"(.+?)?\/>(\r\n?|\n)/', $html, $params );

            /* Strip it */
            $html = preg_replace( '/^<ips:template parameters="(.+?)?"(\s+)?\/>(\r\n?|\n)/', '', $html );

            /* Enforce \n line endings */
            if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
            {
                $html = str_replace( "\r\n", "\n", $html );
            }
            $compiled = Theme::compileTemplate(
                $html,
                $method,
                    $params[1] ?? '',
                true,
                false,
                $this->app->directory,
                $loc,
                $group);
$toEval = <<<EOF
class {$class} {
    {$compiled}
}
EOF;

            try{
                eval($toEval);
            }catch(\ParseError | \Throwable $e){
                $warnings['errors'][] = [
                    'error' => $e->getMessage(),
                    'path' => ['url' => $this->buildPath($file->getRealPath(),0), 'name' => $file->getRealPath()],

                ];
            }
        }

        return $warnings;
    }
}
