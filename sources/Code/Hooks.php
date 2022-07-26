<?php

/**
* @brief      Hooks Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.10
* @version    -storm_version-
*/

namespace IPS\toolbox\Code;

use IPS\toolbox\Code\ParserAbstract;
use OutOfBoundsException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Hooks Class
* @mixin Hooks
*/
class _Hooks extends ParserAbstract
{
    protected $hookFile;
    protected $existingHooks = [];

    protected function getAppPath()
    {
        $appPath = parent::getAppPath();
        $this->hookFile = \json_decode(\file_get_contents($appPath.'data/hooks.json'),true);
        return $appPath.'hooks/';
    }

    protected function getFiles()
    {
        $files = new Finder();
        $files->in($this->getAppPath())->name('*.php');
        if ($this->skip !== null) {
            foreach ($this->skip as $name) {
                $files->notName($name);
            }
        }
        $this->files = $files->files();
    }

    public function validate(){
        $warnings = [];
        foreach($this->files as $file){
            try {
                $this->identify($file);
            }
            catch(\OutOfBoundsException $e){
                $name = $file->getBasename('.php');
                $warnings[$name] = $e->getMessage();
            }
        }

        return $warnings;
    }

    protected function identify(SplFileInfo $file){
        $baseName = $file->getBasename('.php');
        if(!isset($this->hookFile[$baseName])){
            throw new OutOfBoundsException('Hook filed, '.$file->getFilename().', doesn\'t exist');
        }
        $hook = $this->hookFile[$baseName];
        $this->existingHooks[$baseName] = [
            'type' => $hook['type'] === 'C' ? 'class' : 'template',
            'class' => $hook['class'],
            'file' => $file
        ];
    }
}