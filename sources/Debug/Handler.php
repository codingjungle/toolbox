<?php

namespace IPS\toolbox\Debug;

use Whoops\Handler\PrettyPageHandler;

use const DT_WSL_PATH;

class _Handler extends PrettyPageHandler
{


    public function getEditorHref($filePath, $line)
    {
        if(defined('DT_USE_WSL') && DT_USE_WSL === true){
            //\\wsl.localhost\Ubuntu\home\michael\public_html
            $filePath =str_replace('/','\\', $filePath);
            $filePath = DT_WSL_PATH.$filePath;
        }
        return parent::getEditorHref($filePath, $line);
    }
}