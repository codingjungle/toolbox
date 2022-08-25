<?php

namespace IPS\toolbox\extensions\toolbox\phpToolBoxMeta;

use IPS\Settings;
use IPS\Application;
use IPS\toolbox\Form;
use IPS\toolbox\Form\Element;

use function array_keys;
use function defined;
use function header;
use function class_exists;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * devcenter
 */
class _devcenter
{

    public function addJsonMeta(&$jsonMeta)
    {
        $myApps = \defined('MY_APPS') ? json_decode(MY_APPS,true) : [];
        if(empty($myApps) === false){
            foreach($myApps as $app){
                $app = Application::load($app);
                $form = '\\IPS\\' . $app->directory . '\\Form';
                if(class_exists($form)){
                    $jsonMeta['registrar'][] = [
                        'signatures' => [
                            [
                                'class'  => $form,
                                'method' => 'addElement',
                                'index'  => 1,
                            ],
                        ],
                        'provider'   => 'FormAddMethod',
                        'language'   => 'php',
                    ];
                    $element = $form.'\\Element';
                    $jsonMeta['providers'][] = [
                        'name'           => 'FormAddMethod',
                        'lookup_strings' => array_keys($element::getHelpers()),
                    ];
                }
            }
        }

    }
}
