<?php

namespace IPS\toolbox\extensions\toolbox\codeAnalyzer;

use IPS\Application;
use IPS\toolbox\Code\Abstracts\CodeAnalyzerAbstract;
use IPS\toolbox\Code\Abstracts\ParserAbstract;
use IPS\toolbox\ReservedWords;

class _Core extends CodeAnalyzerAbstract
{
    /**
     * use this to target a patter of file
     * @param string $class
     * @return string[]|null
     */
    public function getNames(string $class): ?array
    {
        if($class === 'IPS\\toolbox\\Code\\Db') {
            return ['queries.json'];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Template'){
            $return = [];
            foreach (ReservedWords::get() as $invalidName) {
                $return[] = $invalidName.'.phtml';
            }
            return $return;
        }

        return null;
    }
    public function getPaths(string $class, Application $app)
    {
        if($class === 'IPS\\toolbox\\Code\\ClassScanner') {
            return [$app->getApplicationPath() . DIRECTORY_SEPARATOR . 'sources' . DIRECTORY_SEPARATOR];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Db') {
            return [$app->getApplicationPath() . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Hooks') {
            return [$app->getApplicationPath() . DIRECTORY_SEPARATOR . 'hooks' . DIRECTORY_SEPARATOR];
        }
        elseif($class === 'IPS\toolbox\Code\InterfaceFolder') {
            return [$app->getApplicationPath() . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR];
        }
        elseif($class === 'IPS\toolbox\Code\Template' && $class === 'IPS\toolbox\Code\Templates') {
            return [$app->getApplicationPath() . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR];
        }

        return [$app->getApplicationPath() . DIRECTORY_SEPARATOR];
    }

    public function getExtensions(string $class)
    {
        if($class === 'IPS\\toolbox\\Code\\ClassScanner' && $class === 'IPS\\toolbox\\Code\\Hooks') {
            return ['php'];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Db') {
            return ['json'];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Templates') {
            return ['phtml'];
        }


        return ['php','js','phtml'];
    }

    public function excludedFolders(string $class): ?array
    {
            return [
                'vendor',
                'Vendor',
                'ThirdParty',
                'Thirdparty',
                'thirdparty',
                '3rdParty',
                '3rdparty',
                'Composer',
                'composer'
            ];
    }

    public function excludedFiles(string $class): ?array
    {
        if($class === 'IPS\toolbox\Code\InterfaceFolder') {
            return ['index.html'];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Langs'){
            return [
                'lang.php',
                'jslang.php',
                'lang.xml'
            ];
        }
        elseif($class === 'IPS\\toolbox\\Code\\Langs') {
            return ['settings.json'];
        }
        return null;
    }
}