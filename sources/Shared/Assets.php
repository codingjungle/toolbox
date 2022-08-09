<?php

/**
 * @brief      Assets Trait
 * @author     -storm_author-
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox
 * @since      5.0.20
 * @version    -storm_version-
 */

namespace IPS\toolbox\Shared;

use Exception;
use IPS\Output;
use IPS\Theme;
use IPS\toolbox\Profiler\Debug;
use Throwable;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Assets Class
 */
trait Assets
{
    protected function manage()
    {
        try {
            $menus = \IPS\toolbox\DevCenter\Sources::processedSubMenus();
            Output::i()->output = Theme::i()
                ->getTemplate('generator', 'toolbox', 'front')
                ->sources($this->application->directory, $menus['dev'], 'devcenter', 'dev', 'widget');
        } catch (Throwable|Exception $e) {
            Debug::log($e);
        }
    }

    protected function template()
    {
        $config = [
            'name',
            'group',
            'arguments',
        ];

        $this->doOutput($config, 'template', 'Template');
    }

    protected function doOutput($config, $type, $title)
    {
        try {
            $this->elements->buildForm($config, $type);
            $return = $this->elements->create();
            $output = Theme::i()->getTemplate('generator', 'toolbox', 'front')->wrapper($title, $this->elements->form);

            if ($this->elements->form->valuesError === true) {
                $alt = $this->alt ?? $type;
                Output::i()->output = Theme::i()->getTemplate('generator', 'toolbox', 'front')->sources(
                    $this->application->directory,
                    \IPS\toolbox\DevCenter\Sources::processedSubMenus()['dev'],
                    'devcenter',
                    'dev',
                    $alt,
                    $output
                );
            } elseif ($return === null) {
                Output::i()->output = $output;
            } else {
                Output::i()->json(['msg' => $return, 'type' => 'dtsources']);
            }
        } catch (Throwable|\Exception $e) {
            Debug::log($e);
        }
    }

    protected function controller()
    {
        $config = [
            'name',
            'group',
        ];

        $this->doOutput($config, 'controller', 'Controller');
    }

    protected function module()
    {
        $config = [
            'name',
            'group',
        ];

        $this->doOutput($config, 'module', 'Module');
    }

    protected function debugger()
    {
        $config = [
            'name',
            'group'
        ];
        $this->doOutput($config, 'debugger', 'Debugger');
    }

    protected function widget()
    {
        $config = [
            'name',
            'group',
            'WidgetName',
            'Options'
        ];

        $this->doOutput($config, 'widget', 'Widget');
    }

    protected function jstemplate()
    {
        $config = [
            'name',
            'group',
            'templateName',
        ];

        $this->doOutput($config, 'jstemplate', 'jstemplate');
    }

    protected function jsmixin()
    {
        $config = [
            'name',
            'group',
            'mixin',
        ];

        $this->doOutput($config, 'jsmixin', 'jsmixin');
    }
}
