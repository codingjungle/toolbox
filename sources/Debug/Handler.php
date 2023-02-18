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

        if(defined('DT_USE_CONTAINER') && DT_USE_CONTAINER === true){
            $filePath =str_replace(DT_CONTAINER_GUEST_PATH,'', $filePath);
            $filePath = DT_CONTAINER_HOST_PATH . '/' . $filePath;
        }

        return parent::getEditorHref($filePath, $line);
    }
}
