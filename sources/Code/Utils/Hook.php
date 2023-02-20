<?php

/**
 * @brief       Hook Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       5.0.11
 * @version     -storm_version-
 */


namespace IPS\toolbox\Code\Utils;

use Symfony\Component\Finder\SplFileInfo;

class _Hook
{
    protected SplFileInfo $file;
    protected array $info;
    public function __construct(SplFileInfo $file, array $info){
        $this->file = $file;
        $this->info = $info;
    }

    public function path(){
        return $this->file->getRealPath();
    }

    public function name(){
        return $this->file->getBasename();
    }

    public function isThemHook(){
        return $this->info['type'] === 'S';
    }

    public function isClassHook(){
        return $this->info['type'] === 'C';
    }

    public function getClass(){
        return $this->info['class'];
    }

    public function getContent(){
        $content =  $this->file->getContents();
        $exp = explode("\n", $content);
        unset($exp[0]);
        return implode("\n", $exp);
    }

}
